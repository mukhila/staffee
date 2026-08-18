<?php

namespace Tests\Feature;

use App\Models\Payroll\StatutoryDeduction;
use App\Models\User;
use App\Services\Payroll\StatutoryCalculationService;
use App\Services\Payroll\Statutory\EsiService;
use App\Services\Payroll\Statutory\PfService;
use App\Services\Payroll\Statutory\PtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatutoryDeductionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PfService $pf;
    private EsiService $esi;
    private PtService $pt;
    private StatutoryCalculationService $statutory;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pf       = app(PfService::class);
        $this->esi      = app(EsiService::class);
        $this->pt       = app(PtService::class);
        $this->statutory = app(StatutoryCalculationService::class);
        $this->user     = User::factory()->create(['role' => 'staff']);
    }

    // ── Helper to create a StatutoryDeduction rule ────────────────────────────

    private function makePfRule(array $overrides = []): StatutoryDeduction
    {
        return StatutoryDeduction::create(array_merge([
            'country_code'    => 'IN',
            'rule_type'       => 'pf',
            'employee_rate'   => '12.000000',
            'employer_rate'   => '12.000000',
            'wage_ceiling'    => '15000.000000',
            'effective_from'  => '2020-01-01',
            'status'          => 'active',
        ], $overrides));
    }

    private function makeEsiRule(array $overrides = []): StatutoryDeduction
    {
        return StatutoryDeduction::create(array_merge([
            'country_code'    => 'IN',
            'rule_type'       => 'esi',
            'employee_rate'   => '0.750000',
            'employer_rate'   => '3.250000',
            'wage_ceiling'    => '21000.000000',
            'effective_from'  => '2020-01-01',
            'status'          => 'active',
        ], $overrides));
    }

    private function makePtRule(array $slabs, array $overrides = []): StatutoryDeduction
    {
        return StatutoryDeduction::create(array_merge([
            'country_code'    => 'IN',
            'rule_type'       => 'professional_tax',
            'slab_json'       => $slabs,
            'effective_from'  => '2020-01-01',
            'status'          => 'active',
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PF Tests
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Test 1: PF — salary under ceiling → both contributions calculated correctly.
     */
    public function test_pf_salary_under_ceiling_calculates_correctly(): void
    {
        $rule = $this->makePfRule();

        // Basic salary = 10,000 (under 15,000 ceiling)
        // Employee PF = 10000 * 12% = 1200
        // Employer PF = 10000 * 12% = 1200
        $employeePf = $this->pf->calculateEmployee('10000.000000', $rule);
        $employerPf = $this->pf->calculateEmployer('10000.000000', $rule);

        $this->assertSame(0, bccomp($employeePf, '1200.000000', 6),
            "Employee PF should be 1200.000000, got {$employeePf}");
        $this->assertSame(0, bccomp($employerPf, '1200.000000', 6),
            "Employer PF should be 1200.000000, got {$employerPf}");
    }

    /**
     * Test 2: PF — salary over ceiling → contribution capped at ceiling.
     */
    public function test_pf_salary_over_ceiling_caps_at_ceiling(): void
    {
        $rule = $this->makePfRule(); // ceiling = 15,000

        // Basic salary = 30,000 (over 15,000 ceiling)
        // PF is calculated on 15,000 → Employee = 1800, Employer = 1800
        $employeePf = $this->pf->calculateEmployee('30000.000000', $rule);
        $employerPf = $this->pf->calculateEmployer('30000.000000', $rule);

        $this->assertSame(0, bccomp($employeePf, '1800.000000', 6),
            "Employee PF should be 1800.000000 (capped), got {$employeePf}");
        $this->assertSame(0, bccomp($employerPf, '1800.000000', 6),
            "Employer PF should be 1800.000000 (capped), got {$employerPf}");
    }

    /**
     * Test 3: PF — no active rule → returns zeros.
     */
    public function test_pf_no_active_rule_returns_zeros(): void
    {
        // Do not create any rule
        $result = $this->pf->calculateForPeriod($this->user, '2026-08', '25000.000000');

        $this->assertSame('0.000000', $result['employee_pf']);
        $this->assertSame('0.000000', $result['employer_pf']);
    }

    /**
     * Extra: PF — inactive rule is ignored → returns zeros.
     */
    public function test_pf_inactive_rule_is_ignored(): void
    {
        $this->makePfRule(['status' => 'inactive']);

        $result = $this->pf->calculateForPeriod($this->user, '2026-08', '10000.000000');

        $this->assertSame('0.000000', $result['employee_pf']);
        $this->assertSame('0.000000', $result['employer_pf']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ESI Tests
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Test 4: ESI — salary under threshold → eligible, contributions correct.
     */
    public function test_esi_salary_under_threshold_is_eligible_and_correct(): void
    {
        $rule = $this->makeEsiRule(); // ceiling = 21,000

        // Gross salary = 18,000 (under 21,000)
        // Employee ESI = 18000 * 0.75% = 135
        // Employer ESI = 18000 * 3.25% = 585
        $eligible    = $this->esi->isEligible('18000.000000', $rule);
        $employeeEsi = $this->esi->calculateEmployee('18000.000000', $rule);
        $employerEsi = $this->esi->calculateEmployer('18000.000000', $rule);

        $this->assertTrue($eligible, 'Should be eligible when gross <= wage_ceiling');
        $this->assertSame(0, bccomp($employeeEsi, '135.000000', 6),
            "Employee ESI should be 135.000000, got {$employeeEsi}");
        $this->assertSame(0, bccomp($employerEsi, '585.000000', 6),
            "Employer ESI should be 585.000000, got {$employerEsi}");
    }

    /**
     * Test 5: ESI — salary over threshold → not eligible, returns zeros.
     */
    public function test_esi_salary_over_threshold_returns_zeros(): void
    {
        $rule = $this->makeEsiRule(); // ceiling = 21,000

        // Gross salary = 25,000 (over 21,000 ceiling)
        $eligible    = $this->esi->isEligible('25000.000000', $rule);
        $employeeEsi = $this->esi->calculateEmployee('25000.000000', $rule);
        $employerEsi = $this->esi->calculateEmployer('25000.000000', $rule);

        $this->assertFalse($eligible, 'Should not be eligible when gross > wage_ceiling');
        $this->assertSame('0.000000', $employeeEsi);
        $this->assertSame('0.000000', $employerEsi);
    }

    /**
     * Extra: ESI calculateForPeriod — no active rule → returns zeros + eligible=false.
     */
    public function test_esi_no_active_rule_returns_zeros(): void
    {
        $result = $this->esi->calculateForPeriod($this->user, '2026-08', '18000.000000');

        $this->assertSame('0.000000', $result['employee_esi']);
        $this->assertSame('0.000000', $result['employer_esi']);
        $this->assertFalse($result['eligible']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PT Tests
    // ─────────────────────────────────────────────────────────────────────────

    private function defaultPtSlabs(): array
    {
        return [
            ['from' => 0,     'to' => 10000,  'amount' => 0],
            ['from' => 10001, 'to' => 15000,  'amount' => 150],
            ['from' => 15001, 'to' => null,   'amount' => 200],
        ];
    }

    /**
     * Test 6: PT — salary in a matching slab → correct PT amount returned.
     */
    public function test_pt_salary_in_slab_returns_correct_amount(): void
    {
        $this->makePtRule($this->defaultPtSlabs());

        // Gross = 12,000 → falls in slab [10001–15000] → PT = 150
        $pt = $this->pt->calculateForPeriod($this->user, '2026-08', '12000.000000');

        $this->assertSame(0, bccomp($pt, '150.000000', 6),
            "PT should be 150.000000, got {$pt}");
    }

    /**
     * Test 6b: PT — salary in top open-ended slab.
     */
    public function test_pt_salary_in_top_slab_returns_correct_amount(): void
    {
        $this->makePtRule($this->defaultPtSlabs());

        // Gross = 50,000 → falls in slab [15001–∞] → PT = 200
        $pt = $this->pt->calculateForPeriod($this->user, '2026-08', '50000.000000');

        $this->assertSame(0, bccomp($pt, '200.000000', 6),
            "PT should be 200.000000, got {$pt}");
    }

    /**
     * Test 7: PT — salary below lowest slab → returns zero.
     */
    public function test_pt_salary_below_lowest_slab_returns_zero(): void
    {
        $this->makePtRule($this->defaultPtSlabs());

        // Gross = 5,000 → falls in slab [0–10000] → PT = 0
        $pt = $this->pt->calculateForPeriod($this->user, '2026-08', '5000.000000');

        $this->assertSame(0, bccomp($pt, '0.000000', 6),
            "PT should be 0.000000, got {$pt}");
    }

    /**
     * Extra: PT — no active rule → returns zero.
     */
    public function test_pt_no_active_rule_returns_zero(): void
    {
        $pt = $this->pt->calculateForPeriod($this->user, '2026-08', '20000.000000');

        $this->assertSame('0.000000', $pt);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // StatutoryCalculationService — Orchestrator
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Test 8: calculateStatutory returns all fields with correct totals.
     */
    public function test_calculate_statutory_returns_all_fields_with_correct_totals(): void
    {
        $this->makePfRule();  // PF: 12% / 12%, ceiling 15000
        $this->makeEsiRule(); // ESI: 0.75% / 3.25%, ceiling 21000
        $this->makePtRule($this->defaultPtSlabs());

        // Basic salary = 14,000, Gross = 18,000
        // Employee PF  = 14000 * 12%  = 1680.000000
        // Employer PF  = 14000 * 12%  = 1680.000000
        // Employee ESI = 18000 * 0.75% = 135.000000
        // Employer ESI = 18000 * 3.25% = 585.000000
        // PT           = 150.000000 (slab 10001–15000 ... wait, gross=18000)
        // Actually gross=18000 → slab [15001–∞] → PT = 200
        // Total employee = 1680 + 135 + 200 = 2015.000000
        // Total employer = 1680 + 585 = 2265.000000

        $result = $this->statutory->calculateStatutory(
            $this->user,
            '2026-08',
            '14000.000000',
            '18000.000000'
        );

        $this->assertArrayHasKey('employee_pf', $result);
        $this->assertArrayHasKey('employer_pf', $result);
        $this->assertArrayHasKey('employee_esi', $result);
        $this->assertArrayHasKey('employer_esi', $result);
        $this->assertArrayHasKey('esi_eligible', $result);
        $this->assertArrayHasKey('pt', $result);
        $this->assertArrayHasKey('total_employee_statutory', $result);
        $this->assertArrayHasKey('total_employer_statutory', $result);

        // PF
        $this->assertSame(0, bccomp($result['employee_pf'], '1680.000000', 6),
            "Employee PF should be 1680.000000, got {$result['employee_pf']}");
        $this->assertSame(0, bccomp($result['employer_pf'], '1680.000000', 6),
            "Employer PF should be 1680.000000, got {$result['employer_pf']}");

        // ESI
        $this->assertTrue($result['esi_eligible'], 'Should be ESI eligible at gross 18000');
        $this->assertSame(0, bccomp($result['employee_esi'], '135.000000', 6),
            "Employee ESI should be 135.000000, got {$result['employee_esi']}");
        $this->assertSame(0, bccomp($result['employer_esi'], '585.000000', 6),
            "Employer ESI should be 585.000000, got {$result['employer_esi']}");

        // PT (gross=18000 → slab [15001, null] → 200)
        $this->assertSame(0, bccomp($result['pt'], '200.000000', 6),
            "PT should be 200.000000, got {$result['pt']}");

        // Totals
        $this->assertSame(0, bccomp($result['total_employee_statutory'], '2015.000000', 6),
            "Total employee statutory should be 2015.000000, got {$result['total_employee_statutory']}");
        $this->assertSame(0, bccomp($result['total_employer_statutory'], '2265.000000', 6),
            "Total employer statutory should be 2265.000000, got {$result['total_employer_statutory']}");
    }

    /**
     * Test 8b: calculateStatutory — ESI ineligible (high gross) → esi contributions zero.
     */
    public function test_calculate_statutory_with_ineligible_esi(): void
    {
        $this->makePfRule();  // ceiling = 15000
        $this->makeEsiRule(); // ceiling = 21000
        $this->makePtRule($this->defaultPtSlabs());

        // Basic = 15000 (at ceiling), Gross = 25000 (over ESI ceiling)
        // Employee PF = 15000 * 12% = 1800 (at ceiling)
        // ESI = 0 (ineligible)
        // PT = 200 (gross 25000 → top slab)
        // Total employee = 1800 + 0 + 200 = 2000
        // Total employer = 1800 + 0 = 1800

        $result = $this->statutory->calculateStatutory(
            $this->user,
            '2026-08',
            '15000.000000',
            '25000.000000'
        );

        $this->assertFalse($result['esi_eligible']);
        $this->assertSame('0.000000', $result['employee_esi']);
        $this->assertSame('0.000000', $result['employer_esi']);
        $this->assertSame(0, bccomp($result['employee_pf'], '1800.000000', 6));
        $this->assertSame(0, bccomp($result['total_employee_statutory'], '2000.000000', 6));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 9: BCMath precision — no floating point drift
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Test 9: BCMath precision — ensure no floating point drift in calculations.
     */
    public function test_bcmath_precision_no_floating_point_drift(): void
    {
        // PF: 12% of 15000.123456 should be exactly 1800.014814720000 (BCMath)
        // In float: 15000.123456 * 0.12 = potential drift
        $rule = $this->makePfRule(['wage_ceiling' => null]);

        $employeePf = $this->pf->calculateEmployee('15000.123456', $rule);

        // BCMath exact: 15000.123456 * 12 / 100 = 1800.014814720000...
        // With scale=6: 1800.014814 (truncated, not rounded)
        $expected = bcmul('15000.123456', bcdiv('12.000000', '100.000000', 6), 6);
        $this->assertSame($expected, $employeePf,
            "BCMath result should be deterministic and drift-free");

        // Ensure result is a string, not a float
        $this->assertIsString($employeePf);

        // Ensure result has exactly 6 decimal places
        $this->assertMatchesRegularExpression('/^\d+\.\d{6}$/', $employeePf,
            "Result should have exactly 6 decimal places");
    }

    /**
     * Extra: ESI precision at boundary — salary exactly at wage_ceiling is eligible.
     */
    public function test_esi_salary_exactly_at_ceiling_is_eligible(): void
    {
        $rule = $this->makeEsiRule(); // ceiling = 21,000

        $eligible = $this->esi->isEligible('21000.000000', $rule);

        $this->assertTrue($eligible, 'Salary exactly at ceiling should be eligible (<=)');

        $employeeEsi = $this->esi->calculateEmployee('21000.000000', $rule);
        // 21000 * 0.75% = 157.5
        $expected = bcmul('21000.000000', bcdiv('0.750000', '100.000000', 6), 6);
        $this->assertSame($expected, $employeeEsi);
    }
}
