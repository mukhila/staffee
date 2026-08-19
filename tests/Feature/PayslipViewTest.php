<?php

namespace Tests\Feature;

use App\Models\Payroll\PayrollRun;
use App\Models\Payroll\PayrollSlip;
use App\Models\Payroll\SalaryStructure;
use App\Models\User;
use App\Services\Payroll\PayrollProcessingService;
use Database\Seeders\PayrollComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for P-20 Step 6: payslip show and PDF download endpoints.
 */
class PayslipViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private PayrollSlip $slip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PayrollComponentSeeder::class);

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->employee = User::factory()->create(['role' => 'staff']);

        SalaryStructure::create([
            'user_id'             => $this->employee->id,
            'monthly_base_salary' => '25000.000000',
            'standard_work_days'  => 26,
            'standard_work_hours' => 8,
            'pf_enabled'          => false,
            'esi_enabled'         => false,
            'status'              => 'active',
            'effective_from'      => '2026-08-01',
            'created_by'          => $this->admin->id,
        ]);

        $run = app(PayrollProcessingService::class)->processPayroll(8, 2026);
        $this->slip = PayrollSlip::where('user_id', $this->employee->id)
            ->where('payroll_run_id', $run->id)
            ->firstOrFail();

        // Publish so the staff member can see it
        $this->slip->update(['status' => 'published', 'published_at' => now()]);
    }

    // ── Staff show ────────────────────────────────────────────────────────────

    public function test_staff_can_view_own_payslip(): void
    {
        $this->actingAs($this->employee)
            ->get(route('payroll.slips.show', $this->slip))
            ->assertOk()
            ->assertSee($this->employee->name)
            ->assertSee('Payslip');
    }

    public function test_staff_cannot_view_another_employees_payslip(): void
    {
        $other = User::factory()->create(['role' => 'staff']);

        $this->actingAs($other)
            ->get(route('payroll.slips.show', $this->slip))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_from_payslip_show(): void
    {
        $this->get(route('payroll.slips.show', $this->slip))
            ->assertRedirect(route('login'));
    }

    // ── Admin show ────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_payslip(): void
    {
        $this->actingAs($this->admin)
            ->get(route('payroll.slips.show', $this->slip))
            ->assertOk()
            ->assertSee($this->employee->name);
    }

    // ── PDF download ──────────────────────────────────────────────────────────

    public function test_staff_can_download_own_payslip_pdf(): void
    {
        $response = $this->actingAs($this->employee)
            ->get(route('payroll.slips.download', $this->slip));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_download_any_payslip_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('payroll.slips.download', $this->slip));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_staff_cannot_download_another_employees_payslip_pdf(): void
    {
        $other = User::factory()->create(['role' => 'staff']);

        $this->actingAs($other)
            ->get(route('payroll.slips.download', $this->slip))
            ->assertForbidden();
    }

    // ── Staff payslips list (index) ───────────────────────────────────────────

    public function test_staff_payslips_index_shows_published_slip(): void
    {
        $this->actingAs($this->employee)
            ->get(route('staff.payslips.index'))
            ->assertOk()
            ->assertSee('View')
            ->assertSee('PDF');
    }

    public function test_staff_payslips_index_shows_only_own_slips(): void
    {
        $other = User::factory()->create(['role' => 'staff']);

        $this->actingAs($other)
            ->get(route('staff.payslips.index'))
            ->assertOk()
            ->assertDontSee($this->slip->slip_number);
    }

    // ── Admin run status view ─────────────────────────────────────────────────

    public function test_admin_run_status_shows_slip_action_links(): void
    {
        $run = PayrollRun::findOrFail($this->slip->payroll_run_id);

        $this->actingAs($this->admin)
            ->get(route('admin.payroll.runs.status', $run))
            ->assertOk()
            ->assertSee(route('payroll.slips.show', $this->slip))
            ->assertSee(route('payroll.slips.download', $this->slip));
    }

    // ── Payslip view content ──────────────────────────────────────────────────

    public function test_payslip_show_renders_gross_and_net(): void
    {
        $this->actingAs($this->employee)
            ->get(route('payroll.slips.show', $this->slip))
            ->assertOk()
            ->assertSee(number_format($this->slip->gross_earnings, 2))
            ->assertSee(number_format($this->slip->net_pay, 2));
    }
}
