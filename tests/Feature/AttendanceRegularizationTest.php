<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\User;
use App\Services\Shift\RegularizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AttendanceRegularizationTest extends TestCase
{
    use RefreshDatabase;

    private RegularizationService $service;
    private User $staff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 10:00:00'); // a Monday
        $this->service = app(RegularizationService::class);
        $this->staff   = User::factory()->create(['role' => 'staff']);
        $this->admin   = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_staff_can_submit_request(): void
    {
        $response = $this->actingAs($this->staff)->post(route('staff.attendance.regularizations.store'), [
            'date'                => '2026-11-13',
            'requested_check_in'  => '09:00',
            'requested_check_out' => '18:00',
            'reason'              => 'Forgot to punch',
        ]);

        $response->assertRedirect(route('staff.attendance.regularizations.index'));
        $this->assertDatabaseHas('attendance_regularizations', [
            'user_id'            => $this->staff->id,
            'requested_check_in' => '09:00:00',
            'status'             => 'pending',
        ]);
    }

    public function test_submit_rejects_date_older_than_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->submit($this->staff, [
            'date'               => '2026-10-01',
            'requested_check_in' => '09:00',
            'reason'             => 'Too old',
        ]);
    }

    public function test_submit_requires_at_least_one_time(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->submit($this->staff, ['date' => '2026-11-13', 'reason' => 'Nothing to change']);
    }

    public function test_submit_rejects_check_out_before_check_in(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->submit($this->staff, [
            'date'                => '2026-11-13',
            'requested_check_in'  => '18:00',
            'requested_check_out' => '09:00',
            'reason'              => 'Wrong order',
        ]);
    }

    public function test_submit_rejects_second_pending_request_for_same_date(): void
    {
        $data = ['date' => '2026-11-13', 'requested_check_in' => '09:00', 'reason' => 'First'];
        $this->service->submit($this->staff, $data);

        $this->expectException(InvalidArgumentException::class);
        $this->service->submit($this->staff, $data);
    }

    public function test_approve_creates_attendance_when_none_exists(): void
    {
        $request = $this->service->submit($this->staff, [
            'date'                => '2026-11-13',
            'requested_check_in'  => '09:00',
            'requested_check_out' => '17:30',
            'reason'              => 'Forgot to punch',
        ]);

        $attendance = $this->service->approve($request, $this->admin, 'ok');

        $this->assertSame('09:00:00', $attendance->check_in);
        $this->assertSame('17:30:00', $attendance->check_out);
        $this->assertSame('present', $attendance->status);
        $this->assertSame(510, (int) $attendance->worked_minutes);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->admin->id, $request->fresh()->reviewed_by);
    }

    public function test_approve_keeps_existing_time_when_only_one_is_requested(): void
    {
        Attendance::create([
            'user_id'  => $this->staff->id,
            'date'     => '2026-11-13',
            'check_in' => '09:15:00',
            'status'   => 'present',
        ]);

        $request = $this->service->submit($this->staff, [
            'date'                => '2026-11-13',
            'requested_check_out' => '18:15',
            'reason'              => 'Missed check-out',
        ]);

        $attendance = $this->service->approve($request, $this->admin);

        $this->assertSame('09:15:00', $attendance->check_in);
        $this->assertSame('18:15:00', $attendance->check_out);
        $this->assertSame(1, Attendance::where('user_id', $this->staff->id)->count());
    }

    public function test_reviewer_cannot_approve_own_request(): void
    {
        $request = $this->service->submit($this->admin, [
            'date'               => '2026-11-13',
            'requested_check_in' => '09:00',
            'reason'             => 'Self',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->approve($request, $this->admin);
    }

    public function test_request_cannot_be_reviewed_twice(): void
    {
        $request = $this->service->submit($this->staff, [
            'date'               => '2026-11-13',
            'requested_check_in' => '09:00',
            'reason'             => 'Once',
        ]);
        $this->service->reject($request, $this->admin, 'No evidence');

        $this->expectException(InvalidArgumentException::class);
        $this->service->approve($request->fresh(), $this->admin);
    }

    public function test_admin_can_approve_and_reject_over_http(): void
    {
        $first = $this->service->submit($this->staff, [
            'date' => '2026-11-13', 'requested_check_in' => '09:00', 'reason' => 'A',
        ]);
        $second = $this->service->submit($this->staff, [
            'date' => '2026-11-12', 'requested_check_in' => '09:00', 'reason' => 'B',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.attendances.regularizations.approve', $first))
            ->assertRedirect();
        $this->actingAs($this->admin)
            ->post(route('admin.attendances.regularizations.reject', $second), ['review_notes' => 'Not valid'])
            ->assertRedirect();

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('rejected', $second->fresh()->status);
        $this->assertSame(0, Attendance::whereDate('date', '2026-11-12')->count());
    }

    public function test_staff_cannot_reach_admin_review_routes(): void
    {
        $request = AttendanceRegularization::create([
            'user_id' => $this->admin->id, 'date' => '2026-11-13',
            'requested_check_in' => '09:00:00', 'reason' => 'x', 'status' => 'pending',
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.attendances.regularizations.index'))
            ->assertForbidden();
        $this->actingAs($this->staff)
            ->post(route('admin.attendances.regularizations.approve', $request))
            ->assertForbidden();
    }

    public function test_pages_render(): void
    {
        $this->service->submit($this->staff, [
            'date' => '2026-11-13', 'requested_check_in' => '09:00', 'reason' => 'Shown on both pages',
        ]);

        $this->actingAs($this->staff)
            ->get(route('staff.attendance.regularizations.index'))
            ->assertOk()->assertSee('Shown on both pages');
        $this->actingAs($this->admin)
            ->get(route('admin.attendances.regularizations.index'))
            ->assertOk()->assertSee('Shown on both pages');
    }
}
