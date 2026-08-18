<?php

namespace Tests\Unit;

use App\Models\HR\EmployeeProfile;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for SettlementService.
 *
 * Covers:
 *  - calculateGratuity: rounding logic (>= 6 months → rounds up)
 *  - calculateGratuity: gratuity ceiling cap
 *  - calculateNoticeShortfall: full notice served → zero shortfall
 *  - calculateNoticeShortfall: half notice served → correct deduction
 *  - calculateNoticeShortfall: more than required → zero deduction
 *  - Leave encashment: only is_encashable leave types are included
 *  - Leave encashment: scoped to settlement year (not current year)
 *
 * ── BCMath precision note ──
 * Gratuity = baseSalary × (15/26) × years.
 * With BCMath scale=6:  15/26 = 0.576923 (truncated, not rounded).
 * Expected values in tests reflect the actual BCMath-truncated result.
 *
 * ── Status-enum fix (P-21 finding, fixed in P-32+P-33 commit) ──
 * SettlementService::calculateNoticeShortfall() previously queried status =
 * 'hr_approved', but the resignation_requests migration only has 'approved'.
 * Fixed by changing the query to status = 'approved'.
 */
class SettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettlementService $svc;
    private PayrollCalculationService $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new PayrollCalculationService();
        $this->svc  = new SettlementService($this->calc);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Make a user whose only salary information is the employee_profile
     * current_salary (no salary structure in DB).  This avoids touching the
     * employee_salary_structures foreign-key chain during gratuity tests.
     */
    private function makeUserWithProfile(array $profileAttrs = []): User
    {
        $user = User::factory()->create(['is_active' => true]);

        EmployeeProfile::factory()->create(array_merge([
            'user_id'            => $user->id,
            'current_salary'     => '60000.00',
            'salary_currency'    => 'INR',
            'notice_period_days' => 30,
        ], $profileAttrs));

        return $user->fresh(['profile']);
    }

    /** Insert a resignation_requests row with the correct 'approved' status. */
    private function insertResignationRow(int $userId, array $attrs = []): void
    {
        DB::table('resignation_requests')->insert(array_merge([
            'user_id'             => $userId,
            'submitted_date'      => now()->subDays(30)->toDateString(),
            'requested_last_date' => now()->toDateString(),
            'notice_period_days'  => 30,
            'resignation_type'    => 'voluntary',
            'reason'              => 'Test',
            'notice_waived'       => 0,
            'status'              => 'approved', // valid enum value (NOT 'hr_approved')
            'created_at'          => now(),
            'updated_at'          => now(),
        ], $attrs));
    }

    /** Call the public calculateFullAndFinal and return the full array. */
    private function runSettlement(User $user, string $lastWorkingDate): array
    {
        return $this->svc->calculateFullAndFinal($user, $lastWorkingDate);
    }

    // ─── calculateGratuity: rounding ─────────────────────────────────────────

    /**
     * 9 years 7 months = 9.583... years → partial ≥ 0.5 → rounds up to 10.
     *
     * BCMath actual: 26000 × (15/26 = 0.576923) = 14999.998 × 10 = 149999.980000
     */
    public function test_gratuity_9_years_7_months_rounds_to_10_years(): void
    {
        $user = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $yearsOfService = 9 + (7 / 12); // ≈ 9.583

        $gratuity = $this->svc->calculateGratuity($user, $yearsOfService);

        // Rounded to 10 years (not 9) — confirm by asserting it's MORE than 9-year result
        $nineYears = $this->svc->calculateGratuity($user, 9.0);
        $this->assertGreaterThan($nineYears, $gratuity, 'Gratuity for 9y7m should exceed 9-year gratuity');

        // Exact BCMath value: 26000 × 0.576923 × 10 = 149999.980000
        $this->assertSame('149999.980000', $gratuity);
    }

    /**
     * 9 years 2 months = 9.167... years → partial < 0.5 → stays at 9.
     *
     * BCMath actual: 26000 × 0.576923 × 9 = 134999.982000
     */
    public function test_gratuity_9_years_2_months_stays_at_9_years(): void
    {
        $user = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $yearsOfService = 9 + (2 / 12); // ≈ 9.167

        $gratuity = $this->svc->calculateGratuity($user, $yearsOfService);

        // Stays at 9 years (not 10) — confirm by asserting it's LESS than 10-year result
        $tenYears = $this->svc->calculateGratuity($user, 10.0);
        $this->assertLessThan($tenYears, $gratuity, 'Gratuity for 9y2m should be less than 10-year gratuity');

        // Exact BCMath value: 26000 × 0.576923 × 9 = 134999.982000
        $this->assertSame('134999.982000', $gratuity);
    }

    public function test_gratuity_exactly_5_years_qualifies(): void
    {
        $user = $this->makeUserWithProfile(['current_salary' => '26000.00']);

        $gratuity = $this->svc->calculateGratuity($user, 5.0);

        // BCMath: 26000 × 0.576923 × 5 = 74999.990000
        $this->assertSame('74999.990000', $gratuity);
        $this->assertGreaterThan('0.000000', $gratuity);
    }

    public function test_gratuity_under_5_years_returns_zero(): void
    {
        $user = $this->makeUserWithProfile(['current_salary' => '26000.00']);

        $gratuity = $this->svc->calculateGratuity($user, 4.9);

        $this->assertSame('0.000000', $gratuity);
    }

    public function test_gratuity_result_is_bcmath_string_not_float(): void
    {
        $user     = $this->makeUserWithProfile(['current_salary' => '50000.00']);
        $gratuity = $this->svc->calculateGratuity($user, 10.0);

        $this->assertIsString($gratuity);
        $this->assertStringNotContainsString('E', $gratuity);
        $this->assertStringNotContainsString('e', $gratuity);
    }

    // ─── calculateGratuity: ceiling cap ──────────────────────────────────────

    public function test_gratuity_is_capped_at_configured_ceiling(): void
    {
        // Without ceiling: 26000 × (15/26) × 20 = 299999.960000
        // Set ceiling to 200000 — result must be capped.
        config(['payroll.gratuity_ceiling' => 200000]);

        $user     = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $gratuity = $this->svc->calculateGratuity($user, 20.0);

        $this->assertSame('200000.000000', $gratuity);
    }

    public function test_gratuity_ceiling_zero_means_no_cap(): void
    {
        config(['payroll.gratuity_ceiling' => 0]);

        $user     = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $gratuity = $this->svc->calculateGratuity($user, 20.0);

        // BCMath actual uncapped: 299999.960000
        $this->assertSame('299999.960000', $gratuity);
    }

    public function test_gratuity_below_ceiling_is_not_capped(): void
    {
        // Ceiling 500000 is much higher than 5-year gratuity 74999.990000
        config(['payroll.gratuity_ceiling' => 500000]);

        $user     = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $gratuity = $this->svc->calculateGratuity($user, 5.0);

        $this->assertSame('74999.990000', $gratuity);
    }

    // ─── calculateNoticeShortfall (via calculateFullAndFinal) ─────────────────

    public function test_full_notice_served_gives_zero_shortfall(): void
    {
        // 60 days served, 30 required → no shortfall
        $lastWorkingDate = now()->toDateString();

        $user = $this->makeUserWithProfile(['notice_period_days' => 30, 'current_salary' => '26000.00']);

        $this->insertResignationRow($user->id, [
            'submitted_date'      => now()->subDays(60)->toDateString(),
            'requested_last_date' => $lastWorkingDate,
        ]);

        $result = $this->runSettlement($user, $lastWorkingDate);

        $this->assertSame(0, $result['notice_shortfall_days']);
        $this->assertSame('0.000000', $result['notice_shortfall_deduction']);
    }

    public function test_half_notice_served_returns_correct_shortfall(): void
    {
        // 15 days served, 30 required → 15 shortfall days
        // dailyRate = 26000 / 26 = 1000; deduction = 15 × 1000 = 15000
        $lastWorkingDate = '2025-01-31';
        $submittedDate   = '2025-01-16';

        $user = $this->makeUserWithProfile(['notice_period_days' => 30, 'current_salary' => '26000.00']);

        $this->insertResignationRow($user->id, [
            'submitted_date'      => $submittedDate,
            'requested_last_date' => $lastWorkingDate,
        ]);

        $result = $this->runSettlement($user, $lastWorkingDate);

        $this->assertEquals(15, $result['notice_shortfall_days']);
        $this->assertSame(0, bccomp($result['notice_shortfall_deduction'], '15000.000000', 6));
    }

    public function test_more_than_required_notice_gives_zero_shortfall(): void
    {
        // 90 days served, 30 required → no shortfall even if service worked.
        $lastWorkingDate = now()->toDateString();

        $user = $this->makeUserWithProfile(['notice_period_days' => 30, 'current_salary' => '26000.00']);

        $this->insertResignationRow($user->id, [
            'submitted_date'      => now()->subDays(90)->toDateString(),
            'requested_last_date' => $lastWorkingDate,
        ]);

        $result = $this->runSettlement($user, $lastWorkingDate);

        $this->assertSame(0, $result['notice_shortfall_days']);
        $this->assertSame('0.000000', $result['notice_shortfall_deduction']);
    }

    public function test_notice_waived_gives_zero_shortfall(): void
    {
        // Only 5 days served, but waived → should be 0 (if service worked).
        $lastWorkingDate = now()->toDateString();

        $user = $this->makeUserWithProfile(['notice_period_days' => 30, 'current_salary' => '26000.00']);

        $this->insertResignationRow($user->id, [
            'submitted_date'      => now()->subDays(5)->toDateString(),
            'requested_last_date' => $lastWorkingDate,
            'notice_waived'       => 1,
        ]);

        $result = $this->runSettlement($user, $lastWorkingDate);

        $this->assertSame(0, $result['notice_shortfall_days']);
        $this->assertSame('0.000000', $result['notice_shortfall_deduction']);
    }

    public function test_no_resignation_record_gives_zero_shortfall(): void
    {
        $user = $this->makeUserWithProfile(['notice_period_days' => 30, 'current_salary' => '26000.00']);
        // No resignation row in DB

        $result = $this->runSettlement($user, now()->toDateString());

        $this->assertSame(0, $result['notice_shortfall_days']);
        $this->assertSame('0.000000', $result['notice_shortfall_deduction']);
    }

    // ─── Leave encashment ─────────────────────────────────────────────────────

    public function test_only_encashable_leave_types_are_included_in_encashment(): void
    {
        $user           = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $settlementYear = (int) now()->year;

        // Encashable leave type — should be included
        $encashable = LeaveType::factory()->encashable()->create();
        LeaveBalance::factory()->create([
            'user_id'       => $user->id,
            'leave_type_id' => $encashable->id,
            'year'          => $settlementYear,
            'accrued_days'  => 10,
            'used_days'     => 0,
            'pending_days'  => 0,
        ]);

        // Non-encashable leave type — must NOT be counted
        $nonEncashable = LeaveType::factory()->create(['is_paid' => true, 'is_encashable' => false]);
        LeaveBalance::factory()->create([
            'user_id'       => $user->id,
            'leave_type_id' => $nonEncashable->id,
            'year'          => $settlementYear,
            'accrued_days'  => 5,
            'used_days'     => 0,
            'pending_days'  => 0,
        ]);

        $result = $this->runSettlement($user, now()->toDateString());

        // Only the 10 encashable days should be counted (normalised to 2dp)
        $this->assertSame('10.00', $result['leave_encashment_days']);
    }

    public function test_unpaid_leave_types_are_not_encashable(): void
    {
        $user           = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $settlementYear = (int) now()->year;

        $unpaid = LeaveType::factory()->unpaid()->create();
        LeaveBalance::factory()->create([
            'user_id'       => $user->id,
            'leave_type_id' => $unpaid->id,
            'year'          => $settlementYear,
            'accrued_days'  => 15,
            'used_days'     => 0,
            'pending_days'  => 0,
        ]);

        $result = $this->runSettlement($user, now()->toDateString());

        $this->assertSame('0.00', $result['leave_encashment_days']);
    }

    public function test_leave_encashment_uses_settlement_year_not_current_year(): void
    {
        $user = $this->makeUserWithProfile(['current_salary' => '26000.00']);

        // Settlement is in 2024 — only that year's balance should be used
        $settlementDate = '2024-12-31';
        $settlementYear = 2024;
        $currentYear    = (int) now()->year; // 2026

        $encashable = LeaveType::factory()->encashable()->create();

        // Balance for the settlement year (8 days) — must be included
        LeaveBalance::factory()->create([
            'user_id'       => $user->id,
            'leave_type_id' => $encashable->id,
            'year'          => $settlementYear,
            'accrued_days'  => 8,
            'used_days'     => 0,
            'pending_days'  => 0,
        ]);

        // Balance for current year (20 days) — must NOT be included
        LeaveBalance::factory()->create([
            'user_id'       => $user->id,
            'leave_type_id' => $encashable->id,
            'year'          => $currentYear,
            'accrued_days'  => 20,
            'used_days'     => 0,
            'pending_days'  => 0,
        ]);

        $result = $this->runSettlement($user, $settlementDate);

        $this->assertSame('8.00', $result['leave_encashment_days']);
    }

    public function test_leave_encashment_amount_is_bcmath_string(): void
    {
        $user           = $this->makeUserWithProfile(['current_salary' => '26000.00']);
        $settlementYear = (int) now()->year;

        $encashable = LeaveType::factory()->encashable()->create();
        LeaveBalance::factory()->create([
            'user_id'       => $user->id,
            'leave_type_id' => $encashable->id,
            'year'          => $settlementYear,
            'accrued_days'  => 5,
            'used_days'     => 0,
            'pending_days'  => 0,
        ]);

        $result = $this->runSettlement($user, now()->toDateString());

        $this->assertIsString($result['leave_encashment_amount']);
        $this->assertStringNotContainsString('E', $result['leave_encashment_amount']);
    }
}
