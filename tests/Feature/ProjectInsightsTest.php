<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\Leave\LeaveType;
use App\Models\Milestone;
use App\Models\Payroll\SalaryStructure;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Shift\ShiftHoliday;
use App\Models\Task;
use App\Models\TimeTracker;
use App\Models\User;
use App\Services\Project\ProjectFinanceService;
use App\Services\Project\WorkloadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $alice;
    private User $bob;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-17 10:00:00'); // Tuesday; week starts Mon 16 Nov

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->alice = User::factory()->create(['role' => 'staff', 'is_active' => true, 'name' => 'Alice Example']);
        $this->bob   = User::factory()->create(['role' => 'staff', 'is_active' => true, 'name' => 'Bob Example']);

        $this->project = Project::create([
            'name' => 'Apollo', 'start_date' => '2026-11-01', 'end_date' => '2026-12-31',
            'status' => 'active', 'created_by' => $this->admin->id,
            'budget_amount' => '3000.000000', 'budget_currency' => 'INR', 'budget_hours' => '32.00',
        ]);
        $this->project->users()->attach([$this->alice->id, $this->bob->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** 26,000 a month over 26 days × 8 hours = 125 an hour. */
    private function salary(User $user, string $monthly = '26000.000000', int $hours = 8): void
    {
        SalaryStructure::create([
            'user_id' => $user->id, 'monthly_base_salary' => $monthly, 'standard_work_days' => 26,
            'standard_work_hours' => $hours, 'pf_enabled' => false, 'esi_enabled' => false,
            'status' => 'active', 'effective_from' => '2026-01-01', 'created_by' => $this->admin->id,
        ]);
    }

    private function task(array $attrs = []): Task
    {
        return Task::create($attrs + [
            'project_id' => $this->project->id, 'title' => 'Task ' . uniqid(), 'assigned_to' => $this->alice->id, 'status' => 'pending',
        ]);
    }

    private function entry(User $user, Task $task, string $hours, array $attrs = []): TimeTracker
    {
        return TimeTracker::create($attrs + [
            'user_id' => $user->id, 'trackable_type' => Task::class, 'trackable_id' => $task->id,
            'project_id' => $this->project->id, 'start_time' => '2026-11-10 09:00:00', 'end_time' => '2026-11-10 18:00:00',
            'hours_decimal' => $hours, 'is_billable' => true,
        ]);
    }

    // ── Budget vs actual ──────────────────────────────────────────────────────

    public function test_finance_summary_worked_example(): void
    {
        $this->salary($this->alice); // Bob has no salary structure

        $t1 = $this->task(['estimated_hours' => '20']);
        $t2 = $this->task(['assigned_to' => $this->bob->id]);                 // open, no estimate
        $t3 = $this->task(['estimated_hours' => '5', 'status' => 'completed']); // done: no remaining work

        $this->entry($this->alice, $t1, '10', ['rate_snapshot' => '200']);
        $this->entry($this->alice, $t1, '2', ['approval_status' => 'pending']);          // billable, no rate
        $this->entry($this->bob, $t2, '4', ['is_billable' => false]);                     // not costed
        $this->entry($this->alice, $t1, '5', ['approval_status' => 'rejected', 'rate_snapshot' => '200']); // ignored
        $this->entry($this->alice, $t3, '9', ['end_time' => null]);                       // still running: ignored

        $f = app(ProjectFinanceService::class)->summary($this->project);

        $this->assertSame(0, bccomp($f['hours'], '16', 6));
        $this->assertSame(0, bccomp($f['pending_hours'], '2', 6));
        $this->assertSame(0, bccomp($f['cost'], '1500', 6));            // 12h × 125
        $this->assertSame(0, bccomp($f['billable_value'], '2000', 6));  // 10h × 200
        $this->assertSame(0, bccomp($f['margin'], '500', 6));
        $this->assertSame(0, bccomp($f['uncosted_hours'], '4', 6));
        $this->assertSame(0, bccomp($f['unrated_hours'], '2', 6));
        $this->assertSame(50, $f['budget_used_pct']);
        $this->assertSame(50, $f['hours_used_pct']);

        // T1: 20h estimate − 12h logged (rejected excluded) = 8h left; T3 is completed.
        $this->assertSame(0, bccomp($f['remaining_hours'], '8', 6));
        $this->assertSame(0, bccomp($f['projected_hours'], '24', 6));
        $this->assertSame(0, bccomp($f['projected_cost'], '2500', 6)); // 1500 + 8 × 125
        $this->assertSame(83, $f['projected_pct']);
        $this->assertSame(1, $f['unestimated_open']);
    }

    public function test_finance_summary_with_no_budget_or_entries(): void
    {
        $this->project->update(['budget_amount' => null, 'budget_hours' => null]);

        $f = app(ProjectFinanceService::class)->summary($this->project->fresh());

        $this->assertSame(0, bccomp($f['cost'], '0', 6));
        $this->assertNull($f['budget_used_pct']);
        $this->assertNull($f['hours_used_pct']);
        $this->assertNull($f['projected_cost']);
    }

    public function test_project_page_shows_money_only_to_people_allowed_to_see_payroll(): void
    {
        $this->salary($this->alice);
        $this->entry($this->alice, $this->task(), '10', ['rate_snapshot' => '200']);

        $pmRole = Role::create(['name' => 'PM', 'slug' => 'pm', 'is_active' => true]);
        $pmRole->permissions()->attach(Permission::firstOrCreate(['slug' => 'view-projects'], ['name' => 'View Projects', 'category' => 'projects'])->id);
        $pm = User::factory()->create(['role' => 'pm']);

        $this->actingAs($this->admin)->get(route('admin.projects.show', $this->project))
            ->assertOk()->assertSee('Budget and progress')->assertSee('Labour cost so far')->assertSee('INR 1,250');

        $this->actingAs($pm)->get(route('admin.projects.show', $this->project))
            ->assertOk()->assertSee('Hours logged')->assertDontSee('Labour cost so far')->assertDontSee('1,250');
    }

    public function test_budget_and_estimates_are_saved_from_forms(): void
    {
        $this->actingAs($this->admin)->put(route('admin.projects.update', $this->project), [
            'name' => 'Apollo', 'start_date' => '2026-11-01', 'budget_amount' => '45000.50', 'budget_currency' => 'usd', 'budget_hours' => '120',
        ])->assertRedirect();

        $this->project->refresh();
        $this->assertSame(0, bccomp((string) $this->project->budget_amount, '45000.5', 6));
        $this->assertSame('USD', $this->project->budget_currency);

        $this->actingAs($this->admin)->post(route('admin.tasks.store'), [
            'project_id' => $this->project->id, 'title' => 'Estimated', 'assigned_to' => $this->alice->id,
            'start_date' => '2026-11-18', 'due_date' => '2026-11-20', 'estimated_hours' => '6.5',
        ])->assertRedirect();
        $task = Task::where('title', 'Estimated')->firstOrFail();
        $this->assertSame(0, bccomp((string) $task->estimated_hours, '6.5', 2));

        // A task without a start date can still be created.
        $this->actingAs($this->admin)->post(route('admin.tasks.store'), [
            'project_id' => $this->project->id, 'title' => 'No start', 'assigned_to' => $this->alice->id, 'due_date' => '2026-11-20',
        ])->assertSessionHasNoErrors();

        // Editing keeps the start date (it used to be dropped).
        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'project_id' => $this->project->id, 'title' => 'Estimated', 'assigned_to' => $this->alice->id, 'status' => 'in_progress',
            'start_date' => '2026-11-19', 'due_date' => '2026-11-21', 'estimated_hours' => '',
        ])->assertRedirect();
        $task->refresh();
        $this->assertSame('2026-11-19', $task->start_date->toDateString());
        $this->assertNull($task->estimated_hours);

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'project_id' => $this->project->id, 'title' => 'Estimated', 'assigned_to' => $this->alice->id, 'status' => 'pending',
            'start_date' => '2026-11-25', 'due_date' => '2026-11-20',
        ])->assertSessionHasErrors('due_date');
    }

    public function test_backdated_time_needs_approval_and_rejection_sticks(): void
    {
        $task = $this->task(['status' => 'in_progress']);

        // Logged 10 days ago: older than the 7-day limit, so it must wait for approval.
        $this->actingAs($this->alice)->post(route('staff.time-log.store'), [
            'task_id' => $task->id, 'date' => '2026-11-07', 'start_time' => '09:00', 'end_time' => '13:00', 'description' => 'Catch-up',
        ])->assertRedirect();

        $entry = TimeTracker::where('user_id', $this->alice->id)->firstOrFail();
        $this->assertSame('pending', $entry->approval_status);

        $this->actingAs($this->admin)->post(route('admin.time-entries.approvals.reject', $entry), ['approval_notes' => 'Not agreed'])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame('rejected', $entry->approval_status);
        $this->assertSame($this->admin->id, $entry->approved_by);

        // Rejected time does not count against the project.
        $this->assertSame(0, bccomp(app(ProjectFinanceService::class)->summary($this->project)['hours'], '0', 6));
    }

    // ── Timeline ──────────────────────────────────────────────────────────────

    public function test_timeline_shows_only_this_projects_tasks(): void
    {
        $other = Project::create(['name' => 'Other', 'start_date' => '2026-11-01', 'status' => 'active', 'created_by' => $this->admin->id]);
        Task::create(['project_id' => $other->id, 'title' => 'Leaked task', 'assigned_to' => $this->alice->id, 'status' => 'pending', 'due_date' => '2026-11-20']);

        $this->task(['title' => 'Our dated task', 'start_date' => '2026-11-16', 'due_date' => '2026-11-20', 'estimated_hours' => '8']);
        $this->task(['title' => 'Our undated task']);
        Milestone::create(['project_id' => $this->project->id, 'title' => 'Beta release', 'due_date' => '2026-11-27', 'status' => 'pending', 'created_by' => $this->admin->id]);

        $this->actingAs($this->admin)->get(route('admin.projects.timeline', $this->project))
            ->assertOk()
            ->assertSee('Our dated task')
            ->assertDontSee('Leaked task')
            ->assertDontSee('Our undated task')
            ->assertSee('1 task has no dates')
            ->assertSee('Milestone Beta release', false)
            ->assertSee('Today');
    }

    // ── Workload ──────────────────────────────────────────────────────────────

    private function workload(int $weeks = 2): array
    {
        $data = app(WorkloadService::class)->build(collect([$this->alice->fresh(), $this->bob->fresh()]), Carbon::today(), $weeks);

        return $data['rows']->keyBy(fn ($r) => $r['user']->id)->all();
    }

    public function test_capacity_excludes_holidays_and_leave(): void
    {
        // Week of 16 Nov: one public holiday (Thu) and Alice on leave Friday.
        ShiftHoliday::create(['name' => 'Holiday', 'date' => '2026-11-19', 'holiday_type' => 'national', 'is_recurring' => false, 'is_active' => true]);
        $type = LeaveType::factory()->create();
        LeaveRequest::create([
            'user_id' => $this->alice->id, 'leave_type_id' => $type->id, 'type' => $type->code,
            'from_date' => '2026-11-20', 'to_date' => '2026-11-20', 'days' => 1, 'reason' => 'x', 'status' => 'approved',
        ]);

        $rows = $this->workload();

        $this->assertEqualsWithDelta(24.0, $rows[$this->alice->id]['weeks']['2026-11-16']['capacity'], 0.01); // 3 days × 8h
        $this->assertEqualsWithDelta(32.0, $rows[$this->bob->id]['weeks']['2026-11-16']['capacity'], 0.01);   // 4 days × 8h
        $this->assertEqualsWithDelta(40.0, $rows[$this->alice->id]['weeks']['2026-11-23']['capacity'], 0.01);
    }

    public function test_daily_hours_come_from_salary_structure(): void
    {
        $this->salary($this->alice, '26000.000000', 6);

        $rows = $this->workload();

        $this->assertEqualsWithDelta(30.0, $rows[$this->alice->id]['weeks']['2026-11-23']['capacity'], 0.01);
    }

    public function test_remaining_work_is_spread_over_working_days(): void
    {
        // 20h over Mon 23 – Fri 27 Nov = 4h a day, all in week two.
        $this->task(['start_date' => '2026-11-23', 'due_date' => '2026-11-27', 'estimated_hours' => '20']);
        // 10h estimate, 4h already logged → 6h over Wed 18 – Tue 24 (5 working days) → 1.2h a day.
        $t = $this->task(['start_date' => '2026-11-18', 'due_date' => '2026-11-24', 'estimated_hours' => '10']);
        $this->entry($this->alice, $t, '4');

        $alice = $this->workload()[$this->alice->id];

        $this->assertEqualsWithDelta(3.6, $alice['weeks']['2026-11-16']['load'], 0.01);  // Wed–Fri
        $this->assertEqualsWithDelta(22.4, $alice['weeks']['2026-11-23']['load'], 0.01); // 20 + Mon–Tue 2.4
    }

    public function test_overdue_work_lands_in_current_week_and_overload_is_flagged(): void
    {
        $this->task(['title' => 'Late one', 'start_date' => '2026-11-02', 'due_date' => '2026-11-10', 'estimated_hours' => '50']);
        $this->task(['title' => 'Done one', 'due_date' => '2026-11-18', 'estimated_hours' => '30', 'status' => 'completed']);
        $this->task(['title' => 'Unsized', 'due_date' => '2026-11-25']);

        $alice = $this->workload()[$this->alice->id];
        $thisWeek = $alice['weeks']['2026-11-16'];

        $this->assertEqualsWithDelta(50.0, $thisWeek['load'], 0.01);
        $this->assertTrue($thisWeek['tasks'][0]['overdue']);
        $this->assertSame(125, WorkloadService::percent($thisWeek['load'], $thisWeek['capacity']));
        $this->assertSame(125, $alice['peak_pct']);
        $this->assertSame('Unsized', $alice['weeks']['2026-11-23']['unestimated'][0]['title']);
    }

    public function test_workload_page_filters_and_access(): void
    {
        // 50h due this week is more than Alice's 40h, so she shows as overloaded.
        $this->task(['title' => 'Apollo work', 'start_date' => '2026-11-17', 'due_date' => '2026-11-20', 'estimated_hours' => '50']);
        $outsider = User::factory()->create(['role' => 'staff', 'is_active' => true, 'name' => 'Carol Outsider']);

        $this->actingAs($this->admin)->get(route('admin.projects.workload'))
            ->assertOk()->assertSee('Alice Example')->assertSee('Carol Outsider')->assertSee('more estimated work than available hours');

        $this->actingAs($this->admin)->get(route('admin.projects.workload', ['project_id' => $this->project->id, 'weeks' => 4]))
            ->assertOk()->assertSee('Alice Example')->assertDontSee('Carol Outsider')->assertSee('Apollo work');

        $this->actingAs($this->admin)->get(route('admin.projects.workload', ['weeks' => 3]))->assertSessionHasErrors('weeks');

        $this->actingAs($outsider)->get(route('admin.projects.workload'))->assertForbidden();
    }
}
