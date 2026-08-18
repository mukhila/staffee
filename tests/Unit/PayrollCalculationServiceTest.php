<?php

namespace Tests\Unit;

use App\Services\Payroll\PayrollCalculationService;
use Tests\TestCase;

/**
 * Unit tests for PayrollCalculationService pure-math methods.
 *
 * All money is BCMath at scale 6.  No DB touched here — we test only the
 * stateless arithmetic helpers, not the orchestration methods that query the
 * database (getGrossSalary, getDeductions, etc.).
 */
class PayrollCalculationServiceTest extends TestCase
{
    private PayrollCalculationService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new PayrollCalculationService();
    }

    // ─── normalizeDecimal ─────────────────────────────────────────────────────

    public function test_normalize_decimal_pads_fraction_to_scale(): void
    {
        $this->assertSame('100.000000', $this->svc->normalizeDecimal('100'));
        $this->assertSame('100.000000', $this->svc->normalizeDecimal(100));
        $this->assertSame('100.500000', $this->svc->normalizeDecimal('100.5'));
    }

    public function test_normalize_decimal_truncates_excess_precision(): void
    {
        // Truncates, does NOT round
        $this->assertSame('1.123456', $this->svc->normalizeDecimal('1.1234567890'));
    }

    public function test_normalize_decimal_null_and_empty_become_zero(): void
    {
        $this->assertSame('0.000000', $this->svc->normalizeDecimal(null));
        $this->assertSame('0.000000', $this->svc->normalizeDecimal(''));
    }

    // ─── calculateDailyRate ───────────────────────────────────────────────────

    public function test_daily_rate_is_base_salary_divided_by_working_days(): void
    {
        // 52000 / 26 = 2000
        $result = $this->svc->calculateDailyRate('52000', 26);
        $this->assertSame('2000.000000', $result);
    }

    public function test_daily_rate_uses_bcmath_precision_6(): void
    {
        // 10000 / 30 = 333.333333...  — BCMath truncates at scale 6
        $result = $this->svc->calculateDailyRate('10000', 30);
        $this->assertSame('333.333333', $result);
    }

    public function test_daily_rate_with_zero_working_days_returns_salary_not_division_error(): void
    {
        // max($workingDays, 1) means 0 days → divide by 1 → salary unchanged
        $result = $this->svc->calculateDailyRate('50000', 0);
        $this->assertSame('50000.000000', $result);
    }

    public function test_daily_rate_with_large_salary(): void
    {
        // 1_000_000 / 26 = 38461.538461...
        $result = $this->svc->calculateDailyRate('1000000', 26);
        $this->assertSame('38461.538461', $result);
    }

    // ─── multiplyAmount ───────────────────────────────────────────────────────

    public function test_multiply_amount_basic(): void
    {
        $this->assertSame('200.000000', $this->svc->multiplyAmount('20', '10'));
    }

    public function test_multiply_amount_by_zero_returns_zero(): void
    {
        $this->assertSame('0.000000', $this->svc->multiplyAmount('99999', '0'));
    }

    public function test_multiply_amount_fractional_factor(): void
    {
        // Daily rate 2000 × 0.5 leave days = 1000
        $this->assertSame('1000.000000', $this->svc->multiplyAmount('2000', '0.5'));
    }

    // ─── addAmount / subtractAmount ───────────────────────────────────────────

    public function test_add_amount(): void
    {
        $this->assertSame('300.000000', $this->svc->addAmount('100', '200'));
    }

    public function test_subtract_amount(): void
    {
        $this->assertSame('50.000000', $this->svc->subtractAmount('150', '100'));
    }

    // ─── divideAmount ─────────────────────────────────────────────────────────

    public function test_divide_amount_by_zero_returns_zero_string(): void
    {
        $this->assertSame('0.000000', $this->svc->divideAmount('999', '0'));
    }

    // ─── calculateLwpDeduction (via adjustForLeave) ───────────────────────────

    public function test_lwp_deduction_proportional_to_leave_days(): void
    {
        // baseSalary=52000, workingDays=26 → dailyRate=2000; lwpDays=3 → 6000
        $result = $this->svc->adjustForLeave('52000', '3', 26);
        $this->assertSame('6000.000000', $result);
    }

    public function test_lwp_deduction_zero_leave_returns_zero(): void
    {
        $result = $this->svc->adjustForLeave('52000', '0', 26);
        $this->assertSame('0.000000', $result);
    }

    public function test_lwp_deduction_half_day_leave(): void
    {
        // dailyRate = 52000/26 = 2000; 0.5 days = 1000
        $result = $this->svc->adjustForLeave('52000', '0.5', 26);
        $this->assertSame('1000.000000', $result);
    }

    public function test_lwp_deduction_full_month_leave_equals_full_salary(): void
    {
        // All 26 working days absent → deduction == baseSalary exactly
        $result = $this->svc->adjustForLeave('52000', '26', 26);
        $this->assertSame('52000.000000', $result);
    }

    public function test_lwp_deduction_result_is_bcmath_string_not_float(): void
    {
        $result = $this->svc->adjustForLeave('100000', '7', 30);
        $this->assertIsString($result);
        // Must NOT be a PHP float (scientific notation / imprecision markers)
        $this->assertStringNotContainsString('E', $result);
        $this->assertStringNotContainsString('e', $result);
    }

    // ─── No float drift on large salary ──────────────────────────────────────

    public function test_large_salary_arithmetic_stays_exact(): void
    {
        // PHP float 99999999.99 + 0.01 is imprecise; BCMath must be exact.
        $result = $this->svc->addAmount('99999999.990000', '0.010000');
        $this->assertSame('100000000.000000', $result);
    }
}
