<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\HR\EmployeeProfile;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ReportSubscription;
use App\Models\Role;
use App\Models\Shift\AttendanceException;
use App\Models\Shift\ShiftHoliday;
use App\Models\User;
use App\Notifications\ScheduledReportNotification;
use App\Services\Reports\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScheduledReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private Department $eng;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-17 10:00:00'); // Tuesday

        $this->eng   = Department::create(['name' => 'Engineering', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true, 'name' => 'Priya Raman', 'department_id' => $this->eng->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function csvRows(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);

        return array_map(fn ($line) => str_getcsv($line, ',', '"', ''), array_filter(explode("\n", trim($csv))));
    }

    // ── Periods ───────────────────────────────────────────────────────────────

    public function test_periods(): void
    {
        $service = app(ReportService::class);

        [$from, $to] = $service->period('last-week');
        $this->assertSame(['2026-11-09', '2026-11-15'], [$from->toDateString(), $to->toDateString()]);

        [$from, $to] = $service->period('last-month', Carbon::parse('2026-03-31'));
        $this->assertSame(['2026-02-01', '2026-02-28'], [$from->toDateString(), $to->toDateString()]);
    }

    // ── Report contents ───────────────────────────────────────────────────────

    public function test_attendance_summary_worked_example(): void
    {
        // Week of 9–13 Nov; Wednesday is a holiday, so 4 working days.
        ShiftHoliday::create(['name' => 'Holiday', 'date' => '2026-11-11', 'holiday_type' => 'national', 'is_recurring' => false, 'is_active' => true]);
        foreach (['2026-11-09' => 'present', '2026-11-10' => 'halfday', '2026-11-12' => 'absent', '2026-11-13' => 'leave'] as $date => $status) {
            Attendance::create(['user_id' => $this->staff->id, 'date' => $date, 'status' => $status, 'worked_minutes' => $status === 'present' ? 540 : ($status === 'halfday' ? 240 : null), 'overtime_minutes' => $status === 'present' ? 60 : null]);
        }
        $shift = \App\Models\Shift\Shift::forceCreate(['name' => 'Day', 'code' => 'DAY', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'created_by' => $this->admin->id]);
        AttendanceException::create(['user_id' => $this->staff->id, 'attendance_id' => Attendance::first()->id, 'shift_id' => $shift->id, 'date' => '2026-11-09', 'exception_type' => 'late_arrival', 'status' => 'pending']);

        $service = app(ReportService::class);
        [$from, $to, $range] = $service->period('last-week');
        $built = $service->build($service->find('attendance-summary'), $this->admin, $from, $to, $range);

        $rows = $this->csvRows($built['csv']);
        $this->assertSame('Employee', $rows[0][0]);
        $this->assertSame(['Priya Raman', 'Engineering', '4', '1', '1', '1', '1', '1', '13', '1', '38'], $rows[1]);
        $this->assertContains('Average attendance: 38%.', $built['summary']);
    }

    public function test_leave_summary_and_department_filter(): void
    {
        $other = User::factory()->create(['role' => 'staff', 'is_active' => true, 'name' => 'Sam Seller']);
        $type  = LeaveType::factory()->create(['name' => 'Casual']);
        foreach ([[$this->staff, 'approved', 2], [$this->staff, 'pending', 1], [$other, 'approved', 1]] as [$u, $status, $days]) {
            LeaveRequest::create(['user_id' => $u->id, 'leave_type_id' => $type->id, 'type' => $type->code, 'from_date' => '2026-11-10', 'to_date' => '2026-11-11', 'days' => $days, 'reason' => 'x', 'status' => $status]);
        }

        $service = app(ReportService::class);
        [$from, $to, $range] = $service->period('last-week');
        $built = $service->build($service->find('leave-summary'), $this->admin, $from, $to, $range, $this->eng->id);

        $rows = $this->csvRows($built['csv']);
        $this->assertCount(2, $rows); // header + Priya only
        $this->assertSame(['Priya Raman', 'Engineering', 'Casual', '2', '1', '0'], $rows[1]);
        $this->assertStringContainsString('engineering', $built['filename']);
    }

    public function test_headcount_counts_joiners_and_totals(): void
    {
        EmployeeProfile::create(['user_id' => $this->staff->id, 'joining_date' => '2026-11-10']);
        User::factory()->create(['role' => 'staff', 'is_active' => true])->forceFill(['employment_status' => 'probation'])->save();

        $service = app(ReportService::class);
        [$from, $to, $range] = $service->period('last-week');
        $rows = $this->csvRows($service->build($service->find('headcount'), $this->admin, $from, $to, $range)['csv']);

        $this->assertSame(['Engineering', '1', '1', '0', '0'], $rows[1]);
        $this->assertSame(['Total', '2', '1', '1', '0'], end($rows));
    }

    public function test_project_report_hides_money_without_payroll_permission(): void
    {
        Project::create(['name' => 'Apollo', 'start_date' => '2026-11-01', 'status' => 'active', 'created_by' => $this->admin->id, 'budget_amount' => '1000', 'budget_currency' => 'INR']);
        $role = Role::create(['name' => 'PM', 'slug' => 'pm', 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'view-projects'], ['name' => 'View Projects', 'category' => 'projects'])->id);
        $pm = User::factory()->create(['role' => 'pm']);

        $service = app(ReportService::class);
        [$from, $to, $range] = $service->period('last-week');

        $pmHeader    = $this->csvRows($service->build($service->find('project-status'), $pm, $from, $to, $range)['csv'])[0];
        $adminHeader = $this->csvRows($service->build($service->find('project-status'), $this->admin, $from, $to, $range)['csv'])[0];

        $this->assertNotContains('Labour cost', $pmHeader);
        $this->assertContains('Labour cost', $adminHeader);
    }

    public function test_spreadsheet_formulas_are_neutralised(): void
    {
        $this->staff->update(['name' => '=HYPERLINK("http://evil.example")']);

        $service = app(ReportService::class);
        [$from, $to, $range] = $service->period('last-week');
        $csv = $service->build($service->find('attendance-summary'), $this->admin, $from, $to, $range)['csv'];

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    // ── Page and access ───────────────────────────────────────────────────────

    public function test_staff_without_report_access_are_refused(): void
    {
        $this->actingAs($this->staff)->get(route('reports.scheduled.index'))->assertForbidden();
        $this->actingAs($this->staff)->get(route('reports.scheduled.download', ['report' => 'attendance-summary', 'period' => 'last-week']))
            ->assertSessionHasErrors('report');
    }

    public function test_page_subscribe_download_and_manage(): void
    {
        $this->actingAs($this->admin)->get(route('reports.scheduled.index'))
            ->assertOk()->assertSee('Attendance summary')->assertSee('Project status');

        $this->actingAs($this->admin)->post(route('reports.scheduled.store'), ['report' => 'leave-summary', 'frequency' => 'weekly', 'department_id' => $this->eng->id])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Monday 23 November'));
        $this->actingAs($this->admin)->post(route('reports.scheduled.store'), ['report' => 'leave-summary', 'frequency' => 'weekly', 'department_id' => $this->eng->id])
            ->assertSessionHas('error');

        // Department is dropped for reports that do not use it.
        $this->actingAs($this->admin)->post(route('reports.scheduled.store'), ['report' => 'headcount', 'frequency' => 'monthly', 'department_id' => $this->eng->id]);
        $this->assertNull(ReportSubscription::where('report', 'headcount')->first()->department_id);

        $sub = ReportSubscription::where('report', 'leave-summary')->firstOrFail();
        $this->actingAs($this->admin)->patch(route('reports.scheduled.toggle', $sub));
        $this->assertFalse($sub->fresh()->is_active);

        $other = User::factory()->create(['role' => 'admin']);
        $this->actingAs($other)->delete(route('reports.scheduled.destroy', $sub))->assertNotFound();

        $download = $this->actingAs($this->admin)->get(route('reports.scheduled.download', ['report' => 'headcount', 'period' => 'this-month']));
        $download->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Department,"Active staff"', $download->getContent());
    }

    public function test_send_now_emails_the_report(): void
    {
        Notification::fake();
        $sub = ReportSubscription::create(['user_id' => $this->admin->id, 'report' => 'headcount', 'frequency' => 'monthly']);

        $this->actingAs($this->admin)->post(route('reports.scheduled.send', $sub))->assertSessionHas('success');

        Notification::assertSentTo($this->admin, ScheduledReportNotification::class, function ($n) {
            $mail = $n->toMail($this->admin);
            return str_contains($mail->subject, 'Headcount by department')
                && $mail->rawAttachments[0]['name'] === $n->report['filename'];
        });
    }

    // ── Scheduler ─────────────────────────────────────────────────────────────

    public function test_due_dates(): void
    {
        $weekly  = new ReportSubscription(['frequency' => 'weekly', 'is_active' => true]);
        $monthly = new ReportSubscription(['frequency' => 'monthly', 'is_active' => true]);

        $this->assertTrue($weekly->isDue(Carbon::parse('2026-11-23')));   // Monday
        $this->assertFalse($weekly->isDue(Carbon::parse('2026-11-24')));
        $this->assertTrue($monthly->isDue(Carbon::parse('2026-12-01')));
        $this->assertFalse($monthly->isDue(Carbon::parse('2026-12-02')));

        $weekly->last_sent_at = Carbon::parse('2026-11-23 07:00');
        $this->assertFalse($weekly->isDue(Carbon::parse('2026-11-23')));  // already sent today
    }

    public function test_command_sends_due_reports_once_and_drops_lost_access(): void
    {
        Notification::fake();

        ReportSubscription::create(['user_id' => $this->admin->id, 'report' => 'attendance-summary', 'frequency' => 'weekly']);
        ReportSubscription::create(['user_id' => $this->admin->id, 'report' => 'headcount', 'frequency' => 'monthly']);
        $lost = ReportSubscription::create(['user_id' => $this->staff->id, 'report' => 'attendance-summary', 'frequency' => 'weekly']);

        $this->artisan('reports:send-scheduled', ['--date' => '2026-11-23'])
            ->expectsOutputToContain('1 sent, 1 turned off, 0 failed')
            ->assertSuccessful();

        Notification::assertSentToTimes($this->admin, ScheduledReportNotification::class, 1);
        Notification::assertNotSentTo($this->staff, ScheduledReportNotification::class);
        $this->assertFalse($lost->fresh()->is_active);
        $this->assertStringContainsString('no longer allowed', $lost->fresh()->last_error);

        // Running again the same day does not resend.
        $this->artisan('reports:send-scheduled', ['--date' => '2026-11-23'])->expectsOutputToContain('0 sent');
        Notification::assertSentToTimes($this->admin, ScheduledReportNotification::class, 1);
    }
}
