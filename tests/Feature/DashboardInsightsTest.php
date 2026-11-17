<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Dashboard\DashboardInsightsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private DashboardInsightsService $insights;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-17 11:00:00');
        $this->admin    = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->manager  = User::factory()->create(['role' => 'staff', 'is_active' => true, 'name' => 'Mia Manager']);
        $this->insights = app(DashboardInsightsService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function report(string $name): User
    {
        $u = User::factory()->create(['role' => 'staff', 'is_active' => true, 'name' => $name]);
        $u->forceFill(['reporting_to' => $this->manager->id])->save();

        return $u;
    }

    public function test_team_today_shows_where_each_report_is(): void
    {
        $in      = $this->report('Ian In');
        $done    = $this->report('Fay Finished');
        $leave   = $this->report('Lee Leave');
        $missing = $this->report('Nia Notin');
        $gone    = $this->report('Gone Person');
        $gone->update(['is_active' => false]);

        Attendance::create(['user_id' => $in->id, 'date' => '2026-11-17', 'check_in' => '09:05:00', 'status' => 'present']);
        Attendance::create(['user_id' => $done->id, 'date' => '2026-11-17', 'check_in' => '07:00:00', 'check_out' => '10:30:00', 'status' => 'present']);
        $type = LeaveType::factory()->create();
        LeaveRequest::create([
            'user_id' => $leave->id, 'leave_type_id' => $type->id, 'type' => $type->code,
            'from_date' => '2026-11-16', 'to_date' => '2026-11-18', 'days' => 3, 'reason' => 'x', 'status' => 'approved',
        ]);

        $rows = $this->insights->teamToday($this->manager);

        $this->assertSame(
            [['Ian In', 'in', 'since 09:05'], ['Fay Finished', 'done', '07:00–10:30'], ['Nia Notin', 'not_in', null], ['Lee Leave', 'leave', null]],
            $rows->map(fn ($r) => [$r['user']->name, $r['state'], $r['time']])->all()
        );
        $this->assertSame(0, $this->insights->teamToday($in)->count());
    }

    public function test_company_today_for_admins(): void
    {
        $a = $this->report('A');
        $this->report('B');
        Attendance::create(['user_id' => $a->id, 'date' => '2026-11-17', 'check_in' => '09:00:00', 'status' => 'present']);

        $this->assertSame(['total' => 3, 'in' => 1, 'on_leave' => 0, 'not_in' => 2], $this->insights->companyToday());
    }

    public function test_pending_actions_respect_permissions(): void
    {
        $staff = $this->report('Sam Staff');
        AttendanceRegularization::create(['user_id' => $staff->id, 'date' => '2026-11-16', 'requested_check_in' => '09:00:00', 'reason' => 'x', 'status' => 'pending']);

        // Plain staff can approve nothing, so they see nothing.
        $this->assertCount(0, $this->insights->pendingActions($staff));

        $labels = $this->insights->pendingActions($this->admin)->pluck('count', 'label');
        $this->assertSame(1, $labels['Attendance corrections']);

        // A role with only attendance rights sees only that item, not admin-only ones.
        $role = Role::create(['name' => 'Supervisor', 'slug' => 'supervisor', 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'manage-attendance'], ['name' => 'Manage Attendance', 'category' => 'attendance'])->id);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->assertSame(['Attendance corrections'], $this->insights->pendingActions($supervisor)->pluck('label')->all());

        // You are never asked to approve your own request.
        AttendanceRegularization::query()->update(['user_id' => $supervisor->id]);
        $this->assertCount(0, $this->insights->pendingActions($supervisor));
    }

    public function test_role_cards_render(): void
    {
        $this->report('Ian In');
        $staff = User::where('name', 'Ian In')->first();
        AttendanceRegularization::create(['user_id' => $staff->id, 'date' => '2026-11-16', 'requested_check_in' => '09:00:00', 'reason' => 'x', 'status' => 'pending']);

        $html = view('dashboard._role-cards', [
            'pendingActions' => $this->insights->pendingActions($this->admin),
            'teamToday'      => $this->insights->teamToday($this->manager),
            'companyToday'   => $this->insights->companyToday(),
        ])->render();

        $this->assertStringContainsString('Needs your attention', $html);
        $this->assertStringContainsString('Attendance corrections', $html);
        $this->assertStringContainsString('My team today', $html);
        $this->assertStringContainsString('Ian In', $html);
        $this->assertStringContainsString('checked in', $html);
    }
}
