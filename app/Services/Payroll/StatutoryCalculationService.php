<?php

namespace App\Services\Payroll;

use App\Models\User;
use App\Services\Payroll\Statutory\EsiService;
use App\Services\Payroll\Statutory\PfService;
use App\Services\Payroll\Statutory\PtService;

class StatutoryCalculationService
{
    public function __construct(
        private readonly PfService              $pfService,
        private readonly EsiService             $esiService,
        private readonly PtService              $ptService,
        private readonly PayrollCalculationService $calc
    ) {}

    /**
     * Calculate all statutory deductions and employer contributions for a
     * payroll period.
     *
     * @return array{
     *   employee_pf: string,
     *   employer_pf: string,
     *   employee_esi: string,
     *   employer_esi: string,
     *   esi_eligible: bool,
     *   pt: string,
     *   total_employee_statutory: string,
     *   total_employer_statutory: string,
     * }
     */
    public function calculateStatutory(
        User   $user,
        string $period,
        string $basicSalary,
        string $grossSalary
    ): array {
        $pf  = $this->pfService->calculateForPeriod($user, $period, $basicSalary);
        $esi = $this->esiService->calculateForPeriod($user, $period, $grossSalary);
        $pt  = $this->ptService->calculateForPeriod($user, $period, $grossSalary);

        $totalEmployee = $this->calc->addAmount(
            $this->calc->addAmount($pf['employee_pf'], $esi['employee_esi']),
            $pt
        );

        $totalEmployer = $this->calc->addAmount($pf['employer_pf'], $esi['employer_esi']);

        return [
            'employee_pf'              => $pf['employee_pf'],
            'employer_pf'              => $pf['employer_pf'],
            'employee_esi'             => $esi['employee_esi'],
            'employer_esi'             => $esi['employer_esi'],
            'esi_eligible'             => $esi['eligible'],
            'pt'                       => $pt,
            'total_employee_statutory' => $totalEmployee,
            'total_employer_statutory' => $totalEmployer,
        ];
    }
}
