<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Payroll\PayrollSlip;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-17 09:00:00'); // Tuesday
        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true, 'password' => 'secret-password']);
        $this->other = User::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function token(array $abilities = ['read', 'write'], ?User $user = null): string
    {
        return ($user ?? $this->staff)->createToken('test', $abilities)->plainTextToken;
    }

    private function api(string $token): static
    {
        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    // ── Token management page ─────────────────────────────────────────────────

    public function test_user_can_create_list_and_revoke_tokens(): void
    {
        $this->actingAs($this->staff);

        $this->get(route('profile.api-tokens'))->assertOk()->assertSee('You have no API tokens.');

        $this->post(route('profile.api-tokens.store'), ['name' => 'My phone', 'abilities' => ['read'], 'lifetime' => 30])
            ->assertRedirect(route('profile.api-tokens'))
            ->assertSessionHas('new_token');

        $token = PersonalAccessToken::firstOrFail();
        $this->assertSame(['read'], $token->abilities);
        $this->assertTrue($token->expires_at->isSameDay(now()->addDays(30)));
        $this->assertNotNull(AuditLog::where('event', 'api_token_created')->first());

        $this->get(route('profile.api-tokens'))->assertOk()->assertSee('My phone');

        $this->delete(route('profile.api-tokens.destroy', $token->id))->assertSessionHas('success');
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_token_form_validation(): void
    {
        $this->actingAs($this->staff)
            ->post(route('profile.api-tokens.store'), ['name' => '', 'abilities' => ['admin'], 'lifetime' => 7])
            ->assertSessionHasErrors(['name', 'abilities.0', 'lifetime']);
    }

    public function test_cannot_revoke_someone_elses_token(): void
    {
        $this->other->createToken('theirs', ['read']);
        $theirs = PersonalAccessToken::firstOrFail();

        $this->actingAs($this->staff)->delete(route('profile.api-tokens.destroy', $theirs->id))->assertNotFound();
        $this->assertSame(1, PersonalAccessToken::count());
    }

    // ── Authentication and permissions ────────────────────────────────────────

    public function test_requests_without_a_valid_token_are_rejected_as_json(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->get('/api/v1/me')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->api('not-a-real-token')->get('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_returns_own_profile(): void
    {
        $this->api($this->token(['read']))->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $this->staff->email)
            ->assertJsonPath('data.token_abilities', ['read']);
    }

    public function test_read_only_token_cannot_write(): void
    {
        $this->api($this->token(['read']))->postJson('/api/v1/attendance/check-in')->assertForbidden();
        $this->assertSame(0, Attendance::count());
    }

    public function test_write_only_token_cannot_read(): void
    {
        $this->api($this->token(['write']))->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_expired_token_is_rejected(): void
    {
        $plain = $this->staff->createToken('old', ['read'], now()->subDay())->plainTextToken;

        $this->api($plain)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_deactivated_user_is_rejected(): void
    {
        $token = $this->token();
        $this->staff->update(['is_active' => false]);

        $this->api($token)->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('message', 'This account is deactivated.');
    }

    // ── Attendance ────────────────────────────────────────────────────────────

    public function test_check_in_and_out(): void
    {
        $token = $this->token();

        $this->api($token)->getJson('/api/v1/attendance/today')->assertOk()->assertJsonPath('data', null);

        $this->api($token)->postJson('/api/v1/attendance/check-in')->assertCreated()->assertJsonPath('data.status', 'present');
        $this->api($token)->postJson('/api/v1/attendance/check-in')->assertStatus(422)
            ->assertJsonPath('message', 'You have already checked in today.');

        $this->api($token)->postJson('/api/v1/attendance/check-out')->assertOk();
        $this->assertNotNull(Attendance::firstOrFail()->check_out);
    }

    public function test_check_in_respects_geofence(): void
    {
        foreach (['geofence_enabled' => '1', 'geofence_latitude' => '13.0827', 'geofence_longitude' => '80.2707', 'geofence_radius_m' => '200'] as $k => $v) {
            Setting::create(['key' => $k, 'value' => $v]);
        }
        $token = $this->token();

        $this->api($token)->getJson('/api/v1/attendance/today')->assertJsonPath('meta.location_required', true);
        $this->api($token)->postJson('/api/v1/attendance/check-in')->assertStatus(422);
        $this->api($token)->postJson('/api/v1/attendance/check-in', ['latitude' => 13.0927, 'longitude' => 80.2707])->assertStatus(422);
        $this->api($token)->postJson('/api/v1/attendance/check-in', ['latitude' => 13.0828, 'longitude' => 80.2707])->assertCreated();
    }

    public function test_attendance_history_shows_only_own_records(): void
    {
        Attendance::create(['user_id' => $this->staff->id, 'date' => '2026-11-03', 'check_in' => '09:00:00', 'status' => 'present']);
        Attendance::create(['user_id' => $this->other->id, 'date' => '2026-11-03', 'check_in' => '10:00:00', 'status' => 'present']);

        $this->api($this->token(['read']))->getJson('/api/v1/attendance?month=2026-11')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.check_in', '09:00:00');
    }

    // ── Leave ─────────────────────────────────────────────────────────────────

    public function test_leave_types_list_submit_and_cancel(): void
    {
        $type  = LeaveType::factory()->create(['requires_approval' => false]);
        $token = $this->token();

        $this->api($token)->getJson('/api/v1/leave/types')->assertOk()->assertJsonPath('data.0.id', $type->id);

        $this->api($token)->postJson('/api/v1/leave', [
            'leave_type_id' => $type->id, 'from_date' => '2026-11-24', 'to_date' => '2026-11-24', 'reason' => 'Appointment',
        ])->assertCreated()->assertJsonPath('data.from_date', '2026-11-24');

        $leave = LeaveRequest::firstOrFail();
        $this->api($token)->getJson('/api/v1/leave')->assertOk()->assertJsonCount(1, 'data');

        $this->api($token)->deleteJson("/api/v1/leave/{$leave->id}")->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_leave_validation_and_ownership(): void
    {
        $token = $this->token();

        $this->api($token)->postJson('/api/v1/leave', ['from_date' => '2020-01-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['leave_type_id', 'from_date', 'to_date', 'reason']);

        $type   = LeaveType::factory()->create();
        $theirs = LeaveRequest::create([
            'user_id' => $this->other->id, 'leave_type_id' => $type->id, 'type' => $type->code,
            'from_date' => '2026-11-24', 'to_date' => '2026-11-24', 'days' => 1, 'reason' => 'x', 'status' => 'pending',
        ]);

        $this->api($token)->deleteJson("/api/v1/leave/{$theirs->id}")->assertNotFound();
        $this->assertSame('pending', $theirs->fresh()->status);
    }

    // ── Payslips ──────────────────────────────────────────────────────────────

    public function test_payslips_show_only_own_published_slips(): void
    {
        $this->seed(\Database\Seeders\PayrollComponentSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([$this->staff, $this->other] as $employee) {
            \App\Models\Payroll\SalaryStructure::create([
                'user_id' => $employee->id, 'monthly_base_salary' => '25000.000000',
                'standard_work_days' => 26, 'standard_work_hours' => 8, 'pf_enabled' => false, 'esi_enabled' => false,
                'status' => 'active', 'effective_from' => '2026-08-01', 'created_by' => $admin->id,
            ]);
        }

        // October is published for both; September stays unpublished.
        $september = app(\App\Services\Payroll\PayrollProcessingService::class)->processPayroll(9, 2026);
        $october   = app(\App\Services\Payroll\PayrollProcessingService::class)->processPayroll(10, 2026);
        PayrollSlip::where('payroll_run_id', $october->id)->update(['status' => 'published', 'published_at' => now()]);

        $mine   = PayrollSlip::where('payroll_run_id', $october->id)->where('user_id', $this->staff->id)->firstOrFail();
        $theirs = PayrollSlip::where('payroll_run_id', $october->id)->where('user_id', $this->other->id)->firstOrFail();
        $draft  = PayrollSlip::where('payroll_run_id', $september->id)->where('user_id', $this->staff->id)->firstOrFail();

        $token = $this->token(['read']);

        $this->api($token)->getJson('/api/v1/payslips')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.net_pay', (string) $mine->net_pay);

        $this->api($token)->get("/api/v1/payslips/{$mine->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->api($token)->getJson("/api/v1/payslips/{$theirs->id}/pdf")->assertNotFound();
        $this->api($token)->getJson("/api/v1/payslips/{$draft->id}/pdf")->assertNotFound();
    }
}
