<?php

namespace Tests\Feature;

use App\Models\Payroll\EmployeeLoanInstallment;
use App\Models\Payroll\PayrollSlip;
use App\Models\Payroll\SalaryStructure;
use App\Models\User;
use App\Services\Payroll\BenefitDeductionService;
use App\Services\Payroll\LoanService;
use App\Services\Payroll\PayrollProcessingService;
use Database\Seeders\PayrollComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for P-20 Step 5: verifies that benefit deductions and
 * loan installments are wired into the payroll run and reflected on the slip.
 */
class PayrollIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PayrollProcessingService $service;
    private BenefitDeductionService  $benefitService;
    private LoanService              $loanService;
    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PayrollComponentSeeder::class);

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->employee = User::factory()->create(['role' => 'staff']);
        $this->service  = app(PayrollProcessingService::class);
        $this->benefitService = app(BenefitDeductionService::class);
        $this->loanService    = app(LoanService::class);

        // Minimal active salary structure — no components, no PF/ESI/PT rules,
        // so the gross = monthly_base_salary and other deductions = 0.
        SalaryStructure::create([
            'user_id'             => $this->employee->id,
            'monthly_base_salary' => '30000.000000',
            'standard_work_days'  => 26,
            'standard_work_hours' => 8,
            'pf_enabled'          => false,
            'esi_enabled'         => false,
            'status'              => 'active',
            'effective_from'      => '2026-08-01',
            'created_by'          => $this->admin->id,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function addBenefitDeductions(): void
    {
        $this->benefitService->createDeduction($this->employee, [
            'benefit_name'   => 'Health Insurance',
            'benefit_type'   => 'insurance',
            'amount'         => '500.000000',
            'effective_from' => '2026-08-01',
            'frequency'      => 'monthly',
        ], $this->admin->id);

        $this->benefitService->createDeduction($this->employee, [
            'benefit_name'   => 'Food Voucher',
            'benefit_type'   => 'food_voucher',
            'amount'         => '300.000000',
            'effective_from' => '2026-08-01',
            'frequency'      => 'monthly',
        ], $this->admin->id);
    }

    private function addLoanWithInstallment(): void
    {
        $this->loanService->createLoan($this->employee, [
            'loan_type'             => 'Personal',
            'principal_amount'      => '12000.000000',
            'issued_date'           => '2026-08-01',
            'recovery_start_period' => '2026-08',
            'installment_amount'    => '1000.000000',
            'total_installments'    => 12,
        ], $this->admin->id);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /**
     * Without any benefit deductions or loans, net_pay = gross.
     */
    public function test_payroll_run_with_no_extra_deductions_produces_correct_net(): void
    {
        $run = $this->service->processPayroll(8, 2026);

        $slip = PayrollSlip::where('payroll_run_id', $run->id)
            ->where('user_id', $this->employee->id)
            ->firstOrFail();

        $this->assertSame(0, bccomp((string) $slip->gross_earnings, '30000.000000', 6),
            "Gross should equal base salary when no components exist");
        $this->assertSame(0, bccomp((string) $slip->total_deductions, '0.000000', 6),
            "No deductions expected");
        $this->assertSame(0, bccomp((string) $slip->net_pay, '30000.000000', 6),
            "Net should equal gross when no deductions");
    }

    /**
     * Benefit deductions reduce net pay and appear as BENEFIT_DEDUCTION lines.
     */
    public function test_benefit_deductions_reduce_net_pay(): void
    {
        $this->addBenefitDeductions(); // 500 + 300 = 800

        $run  = $this->service->processPayroll(8, 2026);
        $slip = PayrollSlip::where('payroll_run_id', $run->id)
            ->where('user_id', $this->employee->id)
            ->firstOrFail();

        $this->assertSame(0, bccomp((string) $slip->total_deductions, '800.000000', 6),
            "Total deductions should be 800 (500 insurance + 300 food voucher)");
        $this->assertSame(0, bccomp((string) $slip->net_pay, '29200.000000', 6),
            "Net pay should be 30000 - 800 = 29200");
    }

    /**
     * BENEFIT_DEDUCTION slip lines are created for each active benefit deduction.
     */
    public function test_benefit_deduction_lines_written_to_slip(): void
    {
        $this->addBenefitDeductions(); // 2 deductions

        $run  = $this->service->processPayroll(8, 2026);
        $slip = PayrollSlip::with('lines')
            ->where('payroll_run_id', $run->id)
            ->where('user_id', $this->employee->id)
            ->firstOrFail();

        $benefitLines = $slip->lines->where('line_code', 'BENEFIT_DEDUCTION');
        $this->assertCount(2, $benefitLines,
            "Two BENEFIT_DEDUCTION slip lines expected");

        $amounts = $benefitLines->pluck('amount')->map(fn($a) => (string) $a)->sort()->values();
        $this->assertSame(0, bccomp($amounts[0], '300.000000', 6));
        $this->assertSame(0, bccomp($amounts[1], '500.000000', 6));
    }

    /**
     * Loan installment for the payroll period is deducted from net pay.
     */
    public function test_loan_installment_reduces_net_pay(): void
    {
        $this->addLoanWithInstallment(); // 1000/month for 12 months

        $run  = $this->service->processPayroll(8, 2026);
        $slip = PayrollSlip::where('payroll_run_id', $run->id)
            ->where('user_id', $this->employee->id)
            ->firstOrFail();

        $this->assertSame(0, bccomp((string) $slip->total_deductions, '1000.000000', 6),
            "Total deductions should be 1000 (one loan installment)");
        $this->assertSame(0, bccomp((string) $slip->net_pay, '29000.000000', 6),
            "Net pay should be 30000 - 1000 = 29000");
    }

    /**
     * After payroll run, the due installment is marked as processed.
     */
    public function test_loan_installment_marked_recovered_after_payroll(): void
    {
        $this->addLoanWithInstallment();

        $this->service->processPayroll(8, 2026);

        $installment = EmployeeLoanInstallment::whereHas('loan', fn($q) => $q->where('user_id', $this->employee->id))
            ->where('due_period', '2026-08')
            ->firstOrFail();

        $this->assertSame('processed', $installment->status);
        $this->assertSame(0, bccomp((string) $installment->recovered_amount, '1000.000000', 6),
            "Recovered amount should match the scheduled installment amount");
    }

    /**
     * After payroll, loan remaining_balance is reduced by the recovered installment.
     */
    public function test_loan_balance_reduced_after_payroll(): void
    {
        $this->addLoanWithInstallment(); // principal = 12000

        $this->service->processPayroll(8, 2026);

        $installment = EmployeeLoanInstallment::whereHas('loan', fn($q) => $q->where('user_id', $this->employee->id))
            ->where('due_period', '2026-08')
            ->with('loan')
            ->firstOrFail();

        $this->assertSame(0, bccomp((string) $installment->loan->remaining_balance, '11000.000000', 6),
            "Remaining balance should be 12000 - 1000 = 11000 after one recovered installment");
    }

    /**
     * Benefit deductions and loan installments are both included together.
     */
    public function test_benefit_deductions_and_loan_combined_in_payroll(): void
    {
        $this->addBenefitDeductions(); // 800
        $this->addLoanWithInstallment(); // 1000

        $run  = $this->service->processPayroll(8, 2026);
        $slip = PayrollSlip::with('lines')
            ->where('payroll_run_id', $run->id)
            ->where('user_id', $this->employee->id)
            ->firstOrFail();

        // Total deductions = 800 (benefit) + 1000 (loan) = 1800
        $this->assertSame(0, bccomp((string) $slip->total_deductions, '1800.000000', 6),
            "Combined deductions should be 1800");
        $this->assertSame(0, bccomp((string) $slip->net_pay, '28200.000000', 6),
            "Net pay should be 30000 - 1800 = 28200");

        // All expected line codes present
        $lineCodes = $slip->lines->pluck('line_code');
        $this->assertTrue($lineCodes->contains('BENEFIT_DEDUCTION'), "BENEFIT_DEDUCTION line expected");
        $this->assertTrue($lineCodes->contains('LOAN_RECOVERY'), "LOAN_RECOVERY line expected");

        // Loan installment marked recovered
        $installment = EmployeeLoanInstallment::whereHas('loan', fn($q) => $q->where('user_id', $this->employee->id))
            ->where('due_period', '2026-08')
            ->firstOrFail();
        $this->assertSame('processed', $installment->status);
    }

    /**
     * Paused benefit deductions are excluded from payroll.
     */
    public function test_paused_benefit_deduction_excluded_from_payroll(): void
    {
        $deduction = $this->benefitService->createDeduction($this->employee, [
            'benefit_name'   => 'Gym Membership',
            'benefit_type'   => 'other',
            'amount'         => '200.000000',
            'effective_from' => '2026-08-01',
            'frequency'      => 'monthly',
        ], $this->admin->id);

        $this->benefitService->pause($deduction, $this->admin->id);

        $run  = $this->service->processPayroll(8, 2026);
        $slip = PayrollSlip::where('payroll_run_id', $run->id)
            ->where('user_id', $this->employee->id)
            ->firstOrFail();

        $this->assertSame(0, bccomp((string) $slip->total_deductions, '0.000000', 6),
            "Paused benefit deductions should not appear in payroll");
    }

    /**
     * Future-period loan installments are not recovered in the current run.
     */
    public function test_future_loan_installments_not_recovered(): void
    {
        $this->addLoanWithInstallment(); // starts 2026-08

        $this->service->processPayroll(8, 2026); // processes August

        // September installment should still be pending
        $septInstallment = EmployeeLoanInstallment::whereHas('loan', fn($q) => $q->where('user_id', $this->employee->id))
            ->where('due_period', '2026-09')
            ->firstOrFail();

        $this->assertSame('pending', $septInstallment->status,
            "September installment should remain pending after August payroll");
    }
}
