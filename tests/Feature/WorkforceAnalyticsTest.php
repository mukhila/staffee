<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkforceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'staff']);
    }

    // ── Workforce ────────────────────────────────────────────────────────────

    public function test_workforce_report_accessible_to_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.workforce'));

        $response->assertOk();
        $response->assertViewIs('admin.analytics.workforce');
    }

    public function test_workforce_report_denied_to_staff(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('admin.analytics.workforce'));

        // staff should be redirected or get 403
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect(),
            'Expected 403 or redirect for staff accessing admin analytics'
        );
    }

    // ── Attendance ───────────────────────────────────────────────────────────

    public function test_attendance_report_renders_with_month_filter(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.attendance', ['year' => 2026, 'month' => 8]));

        $response->assertOk();
        $response->assertViewIs('admin.analytics.attendance');
        $response->assertViewHas('year', 2026);
        $response->assertViewHas('month', 8);
    }

    public function test_attendance_report_defaults_to_current_month(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.attendance'));

        $response->assertOk();
        $response->assertViewHas('year', now()->year);
        $response->assertViewHas('month', now()->month);
    }

    // ── Leave utilisation ────────────────────────────────────────────────────

    public function test_leave_utilisation_report_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.leaves'));

        $response->assertOk();
        $response->assertViewIs('admin.analytics.leaves');
    }

    // ── Turnover ─────────────────────────────────────────────────────────────

    public function test_turnover_report_renders(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.turnover'));

        $response->assertOk();
        $response->assertViewIs('admin.analytics.turnover');
    }
}
