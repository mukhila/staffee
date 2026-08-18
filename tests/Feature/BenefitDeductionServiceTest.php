<?php

namespace Tests\Feature;

use App\Models\Payroll\EmployeeBenefitDeduction;
use App\Models\User;
use App\Services\Payroll\BenefitDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenefitDeductionServiceTest extends TestCase
{
    use RefreshDatabase;

    private BenefitDeductionService $service;
    private User $user;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BenefitDeductionService::class);
        $this->actor   = User::factory()->create(['role' => 'admin']);
        $this->user    = User::factory()->create();
    }

    // ── createDeduction ──────────────────────────────────────────────────────

    public function test_create_deduction_stores_with_correct_bcmath_amount(): void
    {
        $deduction = $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Health Insurance Premium',
            'benefit_type'   => 'insurance',
            'amount'         => '150.500000',
            'effective_from' => '2026-09-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $this->assertDatabaseHas('employee_benefit_deductions', [
            'user_id'      => $this->user->id,
            'benefit_name' => 'Health Insurance Premium',
        ]);

        // Amount stored as BCMath-compatible string value
        $this->assertSame(0, bccomp((string) $deduction->amount, '150.500000', 6));
    }

    public function test_create_deduction_throws_when_effective_to_before_effective_from(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Transport Allowance',
            'benefit_type'   => 'transport',
            'amount'         => '200.000000',
            'effective_from' => '2026-09-01',
            'effective_to'   => '2026-08-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);
    }

    // ── activeDeductionsForPeriod ─────────────────────────────────────────────

    public function test_active_deductions_for_period_returns_effective_deduction(): void
    {
        $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Food Voucher',
            'benefit_type'   => 'food_voucher',
            'amount'         => '50.000000',
            'effective_from' => '2026-09-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $results = $this->service->activeDeductionsForPeriod($this->user, '2026-09');

        $this->assertCount(1, $results);
    }

    public function test_active_deductions_excludes_not_yet_started(): void
    {
        // effective_from is in October — should not appear in September period
        $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Provident Fund',
            'benefit_type'   => 'provident_fund',
            'amount'         => '300.000000',
            'effective_from' => '2026-10-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $results = $this->service->activeDeductionsForPeriod($this->user, '2026-09');

        $this->assertCount(0, $results);
    }

    public function test_active_deductions_excludes_already_ended(): void
    {
        // effective_to is in August — should not appear in September period
        $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Old Transport',
            'benefit_type'   => 'transport',
            'amount'         => '100.000000',
            'effective_from' => '2026-01-01',
            'effective_to'   => '2026-08-31',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $results = $this->service->activeDeductionsForPeriod($this->user, '2026-09');

        $this->assertCount(0, $results);
    }

    public function test_active_deductions_excludes_paused(): void
    {
        $deduction = $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Insurance',
            'benefit_type'   => 'insurance',
            'amount'         => '75.000000',
            'effective_from' => '2026-09-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $this->service->pause($deduction, $this->actor->id);

        $results = $this->service->activeDeductionsForPeriod($this->user, '2026-09');

        $this->assertCount(0, $results);
    }

    // ── pause / resume / terminate ────────────────────────────────────────────

    public function test_pause_changes_status_to_paused(): void
    {
        $deduction = $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Insurance',
            'benefit_type'   => 'insurance',
            'amount'         => '100.000000',
            'effective_from' => '2026-09-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $this->service->pause($deduction, $this->actor->id);
        $deduction->refresh();

        $this->assertSame('paused', $deduction->status);
    }

    public function test_resume_changes_status_to_active(): void
    {
        $deduction = $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Insurance',
            'benefit_type'   => 'insurance',
            'amount'         => '100.000000',
            'effective_from' => '2026-09-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $this->service->pause($deduction, $this->actor->id);
        $this->service->resume($deduction, $this->actor->id);
        $deduction->refresh();

        $this->assertSame('active', $deduction->status);
    }

    public function test_terminate_changes_status_and_sets_effective_to_today(): void
    {
        $deduction = $this->service->createDeduction($this->user, [
            'benefit_name'   => 'Food Voucher',
            'benefit_type'   => 'food_voucher',
            'amount'         => '50.000000',
            'effective_from' => '2026-01-01',
            'frequency'      => 'monthly',
        ], $this->actor->id);

        $this->service->terminate($deduction, $this->actor->id);
        $deduction->refresh();

        $this->assertSame('terminated', $deduction->status);
        $this->assertNotNull($deduction->effective_to);
        $this->assertSame(now()->toDateString(), $deduction->effective_to->toDateString());
    }

    // ── HTTP endpoint ─────────────────────────────────────────────────────────

    public function test_store_endpoint_validates_required_fields(): void
    {
        $this->actingAs($this->actor);

        $response = $this->post(route('admin.payroll.benefit-deductions.store'), []);

        $response->assertSessionHasErrors(['user_id', 'benefit_name', 'benefit_type', 'amount', 'effective_from', 'frequency']);
    }
}
