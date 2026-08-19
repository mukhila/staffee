<?php

namespace App\Services\Payroll;

use App\Models\Payroll\ComponentDefinition;
use App\Services\Payroll\BenefitDeductionService;
use App\Services\Payroll\LoanService;
use App\Models\Payroll\EmployeeBenefitDeduction;
use App\Models\Payroll\PayrollAdjustment;
use App\Models\Payroll\PayrollCalendar;
use App\Models\Payroll\PayrollCalculationLog;
use App\Models\Payroll\PayrollInputSnapshot;
use App\Models\Payroll\PayrollRun;
use App\Models\Payroll\PayrollRunEmployee;
use App\Models\Payroll\PayrollSlip;
use App\Models\Payroll\SalaryStructure;
use App\Models\Payroll\TaxBracket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollProcessingService
{
    public function __construct(
        private readonly PayrollCalculationService $calculationService,
        private readonly BenefitDeductionService   $benefitDeductionService,
        private readonly LoanService               $loanService,
    ) {}

    public function processPayroll(int $month, int $year): PayrollRun
    {
        return DB::transaction(function () use ($month, $year) {
            $existingRun = PayrollRun::forPeriod($month, $year)
                ->whereIn('status', ['processing', 'calculating', 'pending_approval', 'approved', 'posted', 'paid'])
                ->first();

            if ($existingRun) {
                throw new \RuntimeException("Payroll for {$month}/{$year} has already been started.");
            }

            $calendar = PayrollCalendar::firstOrCreate(
                ['period_code' => Carbon::create($year, $month, 1)->format('Y-m'), 'pay_frequency' => 'monthly'],
                [
                    'period_start' => Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
                    'period_end' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                    'pay_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                    'attendance_cutoff_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                    'timesheet_cutoff_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                    'leave_cutoff_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                    'status' => 'open',
                ]
            );

            $run = PayrollRun::create([
                'payroll_calendar_id' => $calendar->id,
                'for_month' => $month,
                'for_year' => $year,
                'run_type' => 'regular',
                'currency_code' => 'USD',
                'employee_scope_type' => 'all',
                'status' => 'processing',
                'created_by' => Auth::id() ?? User::query()->value('id'),
                'generated_at' => now(),
            ]);

            $this->processRun($run);

            return $run->fresh(['slips.lines', 'runEmployees']);
        });
    }

    public function processRun(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run) {
            $period = [
                'period_start' => $run->calendar->period_start->toDateString(),
                'period_end' => $run->calendar->period_end->toDateString(),
            ];

            $run->update(['status' => 'calculating']);

            $employees = User::query()
                ->whereHas('salaryStructures', fn ($query) => $query->active($period['period_end']))
                ->with(['profile', 'salaryStructures' => fn ($query) => $query->active($period['period_end'])])
                ->get();

            $totals = [
                'gross' => '0.000000',
                'deductions' => '0.000000',
                'net' => '0.000000',
                'tax' => '0.000000',
                'count' => 0,
            ];

            foreach ($employees as $user) {
                $payload = $this->calculateSalary($user, $period);
                $salaryStructure = $payload['salary_structure'];

                PayrollRunEmployee::updateOrCreate(
                    ['payroll_run_id' => $run->id, 'user_id' => $user->id],
                    [
                        'salary_structure_id' => $salaryStructure->id,
                        'employment_status_snapshot' => $user->employment_status ?? 'active',
                        'inclusion_status' => 'included',
                        'source_summary' => [
                            'worked_days' => $payload['worked_days'] ?? '0.0000',
                            'paid_leave_days' => $payload['paid_leave_days'] ?? '0.0000',
                            'unpaid_leave_days' => $payload['unpaid_leave_days'],
                            'overtime_hours' => $payload['overtime_hours'],
                        ],
                    ]
                );

                $slip = PayrollSlip::updateOrCreate(
                    ['payroll_run_id' => $run->id, 'user_id' => $user->id],
                    [
                        'payroll_calendar_id' => $run->payroll_calendar_id,
                        'salary_structure_id' => $salaryStructure->id,
                        'slip_number' => sprintf('PAY-%04d-%02d-%05d', $run->for_year, $run->for_month, $user->id),
                        'currency_code' => $salaryStructure->currency_code,
                        'pay_frequency' => 'monthly',
                        'period_start' => $period['period_start'],
                        'period_end' => $period['period_end'],
                        'status' => 'draft',
                        'snapshot_json' => [
                            'salary_structure_id' => $salaryStructure->id,
                            'version_no' => $salaryStructure->version_no,
                            'run_month' => $run->for_month,
                            'run_year' => $run->for_year,
                        ],
                    ]
                );

                $this->calculationService->buildSlipFromPayload($slip, $payload);
                $slip->save();
                $slip->lines()->delete();

                $lines = array_merge(
                    $payload['earning_lines'],
                    array_filter([$payload['leave_line']]),
                    $payload['deduction_lines'],
                    $payload['employer_lines'],
                );
                $slip->lines()->createMany($lines);

                // Mark loan installments as recovered now that the slip line exists
                $loanInstallments = $payload['loan_installments'] ?? collect();
                if ($loanInstallments->isNotEmpty()) {
                    $slip->loadMissing('lines');
                    $loanLine = $slip->lines->firstWhere('line_code', 'LOAN_RECOVERY');
                    foreach ($loanInstallments as $installment) {
                        $this->loanService->markRecovered(
                            $installment,
                            (string) $installment->scheduled_amount,
                            $loanLine?->id
                        );
                    }
                }

                PayrollInputSnapshot::updateOrCreate(
                    ['payroll_run_id' => $run->id, 'user_id' => $user->id],
                    [
                        'attendance_summary' => [
                            'worked_days' => $payload['worked_days'] ?? '0.0000',
                            'overtime_hours' => $payload['overtime_hours'],
                        ],
                        'leave_summary' => [
                            'paid_leave_days' => $payload['paid_leave_days'] ?? '0.0000',
                            'unpaid_leave_days' => $payload['unpaid_leave_days'],
                            'leave_deduction' => $payload['leave_deduction'],
                        ],
                        'time_summary' => [
                            'overtime_hours' => $payload['overtime_hours'],
                            'overtime_pay' => $payload['overtime_pay'],
                        ],
                        'deduction_summary' => [
                            'tax'               => $payload['tax'],
                            'pf'                => $payload['pf_employee'],
                            'esi'               => $payload['esi_employee'],
                            'professional_tax'  => $payload['professional_tax'],
                            'voluntary'         => $payload['voluntary_deductions'],
                            'loan_recovery'     => $payload['loan_installment_total'],
                            'benefit_deductions' => $payload['benefit_deduction_total'],
                        ],
                        'salary_structure_snapshot' => [
                            'id' => $salaryStructure->id,
                            'version' => $salaryStructure->version_no,
                            'base_salary' => $salaryStructure->base_salary,
                        ],
                        'tax_context_snapshot' => [
                            'tax_regime_id' => $salaryStructure->tax_regime_id,
                            'tax_amount' => $payload['tax'],
                        ],
                        'snapshot_hash' => sha1(json_encode($payload)),
                    ]
                );

                PayrollCalculationLog::create([
                    'payroll_slip_id' => $slip->id,
                    'payroll_run_id' => $run->id,
                    'user_id' => $user->id,
                    'stage' => 'net_calc',
                    'action' => 'payroll_processed',
                    'input_payload' => [
                        'month' => $run->for_month,
                        'year' => $run->for_year,
                        'salary_structure_id' => $salaryStructure->id,
                    ],
                    'output_payload' => [
                        'gross' => $payload['gross'],
                        'deductions' => $payload['total_deductions'],
                        'net' => $payload['net'],
                    ],
                    'performed_by' => Auth::id(),
                    'performed_at' => now(),
                ]);

                $totals['gross'] = $this->calculationService->addAmount($totals['gross'], $payload['gross']);
                $totals['deductions'] = $this->calculationService->addAmount($totals['deductions'], $payload['total_deductions']);
                $totals['net'] = $this->calculationService->addAmount($totals['net'], $payload['net']);
                $totals['tax'] = $this->calculationService->addAmount($totals['tax'], $payload['tax']);
                $totals['count']++;
            }

            $run->update([
                'status' => 'completed',
                'totals_json' => $totals,
                'input_snapshot_hash' => sha1(json_encode($totals)),
            ]);

            return $run;
        });
    }

    public function calculateSalary(User $user, array $period): array
    {
        $payload = $this->calculationService->getNetSalary($user, $period);
        $periodStr = Carbon::parse($period['period_end'])->format('Y-m');

        // ── Benefit deductions ────────────────────────────────────────────────
        $benefitDeductions = $this->benefitDeductionService->activeDeductionsForPeriod($user, $periodStr);
        $benefitTotal = '0.000000';
        $benefitLines = [];
        $benefitDef = ComponentDefinition::where('code', 'BENEFIT_DEDUCTION')->first();

        foreach ($benefitDeductions as $bd) {
            $amount = $this->calculationService->normalizeDecimal((string) $bd->amount);
            $benefitTotal = $this->calculationService->addAmount($benefitTotal, $amount);
            if ($benefitDef) {
                $benefitLines[] = $this->calculationService->buildLinePayload($benefitDef, $amount, [
                    'source_type'           => 'manual_adjustment',
                    'source_reference_type' => EmployeeBenefitDeduction::class,
                    'source_reference_id'   => $bd->id,
                    'calculation_basis'     => $bd->benefit_name,
                ]);
            }
        }

        // ── Loan installment recovery ─────────────────────────────────────────
        $loanInstallments = $this->loanService->dueInstallments($user, $periodStr);
        $loanInstallmentTotal = '0.000000';
        foreach ($loanInstallments as $inst) {
            $loanInstallmentTotal = $this->calculationService->addAmount(
                $loanInstallmentTotal, (string) $inst->scheduled_amount
            );
        }

        if (bccomp($loanInstallmentTotal, '0', 6) === 1) {
            $combinedLoanRecovery = $this->calculationService->addAmount(
                $payload['voluntary_deductions'], $loanInstallmentTotal
            );
            // Rebuild LOAN_RECOVERY line to include both EMIs and voluntary deductions
            $payload['deduction_lines'] = array_values(array_filter(
                $payload['deduction_lines'],
                fn($line) => ($line['line_code'] ?? '') !== 'LOAN_RECOVERY'
            ));
            $loanDef = ComponentDefinition::where('code', 'LOAN_RECOVERY')->first();
            if ($loanDef) {
                $payload['deduction_lines'][] = $this->calculationService->buildLinePayload($loanDef, $combinedLoanRecovery, [
                    'source_type'       => 'manual_adjustment',
                    'calculation_basis' => 'Loan EMI + voluntary deductions',
                ]);
            }
        }

        // ── Update totals ─────────────────────────────────────────────────────
        $additionalDeductions = $this->calculationService->addAmount($benefitTotal, $loanInstallmentTotal);
        $payload['total_deductions'] = $this->calculationService->addAmount(
            $payload['total_deductions'], $additionalDeductions
        );
        $payload['net'] = $this->calculationService->subtractAmount(
            $payload['gross'], $payload['total_deductions']
        );
        $payload['deduction_lines']  = array_values(array_merge($payload['deduction_lines'], $benefitLines));
        $payload['benefit_deduction_total'] = $benefitTotal;
        $payload['loan_installment_total']  = $loanInstallmentTotal;
        $payload['loan_installments']       = $loanInstallments;

        // ── Attendance / leave ────────────────────────────────────────────────
        $payload['worked_days']     = $this->deriveWorkedDays($user, $period);
        $payload['paid_leave_days'] = $this->derivePaidLeaveDays($user, $period);

        return $payload;
    }

    public function applyDeductions(array $salary, User $user, array $period): array
    {
        return $this->calculationService->getDeductions($user, $period, $salary);
    }

    public function calculateTax(string $grossSalary, Collection $taxBrackets): string
    {
        return $this->calculationService->calculateTax($grossSalary, $taxBrackets);
    }

    public function publishPayrollRun(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run) {
            $run->slips()->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            $run->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            return $run->fresh('slips');
        });
    }

    protected function deriveWorkedDays(User $user, array $period): string
    {
        $count = \App\Models\Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$period['period_start'], $period['period_end']])
            ->whereNotNull('check_in')
            ->count();

        return number_format($count, 4, '.', '');
    }

    protected function derivePaidLeaveDays(User $user, array $period): string
    {
        $requests = \App\Models\LeaveRequest::with('leaveType')
            ->approved()
            ->where('user_id', $user->id)
            ->where('from_date', '<=', $period['period_end'])
            ->where('to_date', '>=', $period['period_start'])
            ->get();

        $days = '0.0000';
        foreach ($requests as $leave) {
            if (!$leave->leaveType?->is_paid) {
                continue;
            }

            $days = bcadd($days, (string) $leave->days, 4);
        }

        return $days;
    }
}
