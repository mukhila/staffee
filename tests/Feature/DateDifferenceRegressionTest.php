<?php

namespace Tests\Feature;

use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveType;
use App\Models\Monitoring\MonitoringIdlePeriod;
use App\Models\Monitoring\MonitoringSession;
use App\Models\Monitoring\MonitoringSetting;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeTracker;
use App\Models\User;
use App\Services\Leave\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Carbon 3 returns signed differences: later->diffInDays(earlier) is negative.
 * These lock in the four places that had the arguments the wrong way round.
 */
class DateDifferenceRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-17 10:00:00');
        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function typeWithNotice(int $days): LeaveType
    {
        $type = LeaveType::factory()->create(['requires_approval' => false]);
        LeavePolicy::create([
            'leave_type_id' => $type->id, 'name' => 'Notice policy', 'min_notice_days' => $days,
            'requires_manager_approval' => true, 'is_active' => true,
        ]);

        return $type;
    }

    public function test_leave_with_enough_notice_is_accepted(): void
    {
        $type = $this->typeWithNotice(3);

        $leave = app(LeaveRequestService::class)->submit($this->staff, [
            'leave_type_id' => $type->id, 'from_date' => '2026-11-24', 'to_date' => '2026-11-24', 'reason' => 'Planned',
        ]);

        $this->assertNotNull($leave->id);
    }

    public function test_leave_with_too_little_notice_is_refused(): void
    {
        $type = $this->typeWithNotice(3);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires 3 days advance notice');

        app(LeaveRequestService::class)->submit($this->staff, [
            'leave_type_id' => $type->id, 'from_date' => '2026-11-18', 'to_date' => '2026-11-18', 'reason' => 'Short notice',
        ]);
    }

    public function test_recent_time_entry_does_not_need_approval(): void
    {
        $project = Project::create(['name' => 'P', 'start_date' => '2026-11-01', 'status' => 'active', 'created_by' => $this->staff->id]);
        $task    = Task::create(['project_id' => $project->id, 'title' => 'T', 'assigned_to' => $this->staff->id, 'status' => 'in_progress']);

        $this->actingAs($this->staff)->post(route('staff.time-log.store'), [
            'task_id' => $task->id, 'date' => '2026-11-16', 'start_time' => '09:00', 'end_time' => '10:00',
        ])->assertRedirect();

        $this->assertNull(TimeTracker::firstOrFail()->approval_status);
    }

    public function test_running_timer_reports_positive_elapsed_seconds(): void
    {
        $project = Project::create(['name' => 'P', 'start_date' => '2026-11-01', 'status' => 'active', 'created_by' => $this->staff->id]);
        $task    = Task::create(['project_id' => $project->id, 'title' => 'T', 'assigned_to' => $this->staff->id, 'status' => 'in_progress']);
        TimeTracker::create([
            'user_id' => $this->staff->id, 'trackable_type' => Task::class, 'trackable_id' => $task->id,
            'project_id' => $project->id, 'start_time' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->staff)->getJson(route('time-tracker.active'))
            ->assertOk()
            ->assertJsonPath('running', true)
            ->assertJsonPath('seconds', 300);
    }

    public function test_idle_alert_fires_after_threshold(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        MonitoringSetting::create(['user_id' => null, 'enabled' => true, 'idle_threshold_seconds' => 600]);
        $session = MonitoringSession::create(['user_id' => $this->staff->id, 'started_at' => now()->subHour(), 'status' => 'active']);
        MonitoringIdlePeriod::create([
            'user_id' => $this->staff->id, 'session_id' => $session->id, 'idle_start' => now()->subMinutes(15),
        ]);

        $this->artisan('monitoring:check-idle')->assertSuccessful();

        $alert = Notification::where('type', 'idle_alert')->where('user_id', $admin->id)->first();
        $this->assertNotNull($alert);
        $this->assertStringContainsString('idle for 15 minutes', $alert->message);
    }
}
