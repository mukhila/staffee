<?php

namespace Tests\Feature;

use App\Models\Monitoring\MonitoringActivityLog;
use App\Models\Monitoring\MonitoringAppRule;
use App\Models\Monitoring\MonitoringIdlePeriod;
use App\Models\Monitoring\MonitoringSession;
use App\Models\Monitoring\MonitoringSetting;
use App\Models\User;
use App\Notifications\WeeklyActivitySummaryNotification;
use App\Services\Monitoring\ProductivityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MonitoringProductivityTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $other;
    private User $admin;
    private MonitoringSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        // Monday 16 Nov 2026; "last week" is 9–15 Nov.
        Carbon::setTestNow('2026-11-16 10:00:00');

        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->other = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->session = $this->sessionFor($this->staff);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sessionFor(User $user): MonitoringSession
    {
        return MonitoringSession::create([
            'user_id' => $user->id, 'started_at' => '2026-11-10 09:00:00', 'status' => 'ended',
        ]);
    }

    private function log(string $app, ?string $title, int $seconds, string $at = '2026-11-10 10:00:00', ?User $user = null, bool $active = true): void
    {
        $user ??= $this->staff;

        MonitoringActivityLog::create([
            'user_id'             => $user->id,
            'session_id'          => $user->is($this->staff) ? $this->session->id : $this->sessionFor($user)->id,
            'recorded_at'         => $at,
            'duration_seconds'    => $seconds,
            'active_app_name'     => $app,
            'active_window_title' => $title,
            'is_active'           => $active,
        ]);
    }

    private function rule(string $pattern, string $category, string $type = 'app'): MonitoringAppRule
    {
        return MonitoringAppRule::create(['pattern' => $pattern, 'match_type' => $type, 'category' => $category]);
    }

    private function week(): array
    {
        return [Carbon::parse('2026-11-09')->startOfDay(), Carbon::parse('2026-11-15')->endOfDay()];
    }

    // ── Classification ────────────────────────────────────────────────────────

    public function test_unmatched_activity_is_neutral(): void
    {
        $service = app(ProductivityService::class);

        $this->assertSame('neutral', $service->classify('Notepad', 'notes.txt'));
        $this->assertSame('neutral', $service->classify(null, null));
        $this->assertFalse($service->isCategorised('Notepad', 'notes.txt'));
    }

    public function test_app_rule_matches_case_insensitively(): void
    {
        $this->rule('visual studio', 'productive');

        $this->assertSame('productive', app(ProductivityService::class)->classify('Microsoft Visual Studio Code', null));
    }

    public function test_window_title_rule_beats_app_rule(): void
    {
        $this->rule('Chrome', 'neutral');
        $this->rule('YouTube', 'unproductive', 'title');

        $service = app(ProductivityService::class);

        $this->assertSame('unproductive', $service->classify('Google Chrome', 'Funny cats - YouTube'));
        $this->assertSame('neutral', $service->classify('Google Chrome', 'Jira board'));
    }

    public function test_longest_pattern_wins(): void
    {
        $this->rule('YouTube', 'unproductive', 'title');
        $this->rule('YouTube Studio', 'productive', 'title');

        $this->assertSame(
            'productive',
            app(ProductivityService::class)->classify('Google Chrome', 'Channel dashboard - YouTube Studio')
        );
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    public function test_summary_totals_time_by_category(): void
    {
        $this->rule('Code', 'productive');
        $this->rule('Steam', 'unproductive');

        $this->log('Code', 'app.php', 3600);
        $this->log('Code', 'web.php', 1800, '2026-11-11 10:00:00');
        $this->log('Steam', 'Store', 600);
        $this->log('Notepad', 'todo', 600);
        $this->log('Code', 'ignored: inactive sample', 999, '2026-11-10 11:00:00', null, false);
        $this->log('Code', 'ignored: outside week', 999, '2026-11-02 10:00:00');
        $this->log('Code', 'ignored: someone else', 999, '2026-11-10 10:00:00', $this->other);

        MonitoringIdlePeriod::create([
            'user_id' => $this->staff->id, 'session_id' => $this->session->id,
            'idle_start' => '2026-11-10 12:00:00', 'idle_end' => '2026-11-10 12:10:00', 'duration_seconds' => 600,
        ]);

        [$from, $to] = $this->week();
        $summary = app(ProductivityService::class)->summarise($this->staff->id, $from, $to);

        $this->assertSame(5400, $summary['seconds']['productive']);
        $this->assertSame(600, $summary['seconds']['unproductive']);
        $this->assertSame(600, $summary['seconds']['neutral']);
        $this->assertSame(6600, $summary['active_seconds']);
        $this->assertSame(600, $summary['idle_seconds']);
        $this->assertSame(82, $summary['productive_pct']);
        $this->assertSame('Code', $summary['top_apps'][0]['app']);
        $this->assertSame(5400, $summary['top_apps'][0]['seconds']);
        $this->assertSame('productive', $summary['top_apps'][0]['category']);
    }

    public function test_summary_with_no_activity_is_all_zero(): void
    {
        [$from, $to] = $this->week();
        $summary = app(ProductivityService::class)->summarise($this->staff->id, $from, $to);

        $this->assertSame(0, $summary['active_seconds']);
        $this->assertSame(0, $summary['productive_pct']);
        $this->assertCount(0, $summary['top_apps']);
    }

    public function test_uncategorised_apps_excludes_those_with_a_rule(): void
    {
        $this->rule('Code', 'productive');
        $this->log('Code', null, 3600);
        $this->log('Figma', null, 1200);
        $this->log('Slack', null, 2400);

        [$from, $to] = $this->week();
        $apps = app(ProductivityService::class)->uncategorisedApps($from, $to);

        $this->assertSame(['Slack', 'Figma'], $apps->pluck('app')->all());
    }

    public function test_format_duration(): void
    {
        $this->assertSame('0m', ProductivityService::formatDuration(30));
        $this->assertSame('42m', ProductivityService::formatDuration(42 * 60));
        $this->assertSame('3h 05m', ProductivityService::formatDuration(3 * 3600 + 5 * 60));
    }

    // ── Rules admin ───────────────────────────────────────────────────────────

    public function test_admin_can_add_change_and_remove_rules(): void
    {
        $this->actingAs($this->admin)->post(route('admin.monitoring.app-rules.store'), [
            'pattern' => ' Slack ', 'match_type' => 'app', 'category' => 'productive',
        ])->assertRedirect()->assertSessionHas('success');

        $rule = MonitoringAppRule::firstOrFail();
        $this->assertSame('Slack', $rule->pattern);

        // Adding the same pattern again moves it instead of duplicating it.
        $this->actingAs($this->admin)->post(route('admin.monitoring.app-rules.store'), [
            'pattern' => 'Slack', 'match_type' => 'app', 'category' => 'neutral',
        ]);
        $this->assertSame(1, MonitoringAppRule::count());
        $this->assertSame('neutral', $rule->fresh()->category);

        $this->actingAs($this->admin)->put(route('admin.monitoring.app-rules.update', $rule), ['category' => 'unproductive']);
        $this->assertSame('unproductive', $rule->fresh()->category);

        $this->actingAs($this->admin)->delete(route('admin.monitoring.app-rules.destroy', $rule))->assertRedirect();
        $this->assertSame(0, MonitoringAppRule::count());
    }

    public function test_rule_validation_and_access_control(): void
    {
        $this->actingAs($this->admin)->post(route('admin.monitoring.app-rules.store'), [
            'pattern' => 'x', 'match_type' => 'url', 'category' => 'great',
        ])->assertSessionHasErrors(['pattern', 'match_type', 'category']);

        $this->actingAs($this->staff)->get(route('admin.monitoring.app-rules.index'))->assertForbidden();
        $this->actingAs($this->staff)->post(route('admin.monitoring.app-rules.store'), [
            'pattern' => 'Slack', 'match_type' => 'app', 'category' => 'productive',
        ])->assertForbidden();
    }

    public function test_rules_page_lists_rules_and_uncategorised_apps(): void
    {
        $this->rule('Code', 'productive');
        $this->log('Figma', null, 1200);

        $this->actingAs($this->admin)->get(route('admin.monitoring.app-rules.index'))
            ->assertOk()
            ->assertSee('Code')
            ->assertSee('Figma')
            ->assertSee('Not categorised yet');
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    public function test_productivity_report_renders_with_data(): void
    {
        $this->rule('Code', 'productive');
        $this->log('Code', 'app.php', 3600);

        $this->actingAs($this->admin)
            ->get(route('admin.monitoring.reports.productivity', ['week' => '2026-11-10']))
            ->assertOk()
            ->assertSee($this->staff->name)
            ->assertSee('100%');
    }

    public function test_daily_and_department_reports_render(): void
    {
        MonitoringIdlePeriod::create([
            'user_id' => $this->staff->id, 'session_id' => $this->session->id,
            'idle_start' => '2026-11-10 12:00:00', 'idle_end' => '2026-11-10 12:10:00', 'duration_seconds' => 600,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.monitoring.reports.daily', ['date' => '2026-11-10']))
            ->assertOk();
        $this->actingAs($this->admin)
            ->get(route('admin.monitoring.reports.department', ['from' => '2026-11-01', 'to' => '2026-11-15']))
            ->assertOk();
    }

    // ── My Activity ───────────────────────────────────────────────────────────

    public function test_staff_see_only_their_own_activity(): void
    {
        $this->log('MyEditor', null, 3600);
        $this->log('SecretOtherApp', null, 3600, '2026-11-10 10:00:00', $this->other);

        $this->actingAs($this->staff)
            ->get(route('staff.my-activity.index', ['week' => '2026-11-10']))
            ->assertOk()
            ->assertSee('MyEditor')
            ->assertDontSee('SecretOtherApp');
    }

    public function test_my_activity_shows_empty_state(): void
    {
        $this->actingAs($this->staff)->get(route('staff.my-activity.index'))
            ->assertOk()
            ->assertSee('No activity was recorded for you this week.');
    }

    // ── Weekly email ──────────────────────────────────────────────────────────

    public function test_weekly_summary_is_sent_only_to_monitored_users_with_activity(): void
    {
        Notification::fake();

        $this->log('Code', null, 3600);                                             // staff: has activity
        $disabled = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        MonitoringSetting::create(['user_id' => $disabled->id, 'enabled' => false]);
        $this->log('Code', null, 3600, '2026-11-10 10:00:00', $disabled);           // monitoring off
        $inactive = User::factory()->create(['role' => 'staff', 'is_active' => false]);
        $this->log('Code', null, 3600, '2026-11-10 10:00:00', $inactive);           // account inactive
        // $this->other has no activity at all

        $this->artisan('monitoring:weekly-summary')->assertSuccessful();

        Notification::assertSentTo($this->staff, WeeklyActivitySummaryNotification::class);
        Notification::assertNotSentTo($this->other, WeeklyActivitySummaryNotification::class);
        Notification::assertNotSentTo($disabled, WeeklyActivitySummaryNotification::class);
        Notification::assertNotSentTo($inactive, WeeklyActivitySummaryNotification::class);
    }

    public function test_dry_run_sends_nothing(): void
    {
        Notification::fake();
        $this->log('Code', null, 3600);

        $this->artisan('monitoring:weekly-summary', ['--dry-run' => true])
            ->expectsOutputToContain('Would send to')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_weekly_summary_email_content(): void
    {
        $this->rule('Code', 'productive');
        $this->log('Code', null, 3600);

        [$from, $to] = $this->week();
        $summary = app(ProductivityService::class)->summarise($this->staff->id, $from, $to);
        $summary['top_apps'] = $summary['top_apps']->all();

        $notification = new WeeklyActivitySummaryNotification($summary, '2026-11-09', '2026-11-15');
        $mail = $notification->toMail($this->staff);

        $this->assertStringContainsString('09 Nov', $mail->subject);
        $body = implode("\n", array_merge($mail->introLines, $mail->outroLines));
        $this->assertStringContainsString('**Active time:** 1h 00m', $body);
        $this->assertStringContainsString('(100%)', $body);
        $this->assertStringContainsString('Code: 1h 00m (Productive)', $body);
        $this->assertStringContainsString('my-activity', $mail->actionUrl);
        $this->assertSame('weekly_activity_summary', $notification->toArray($this->staff)['type']);
    }
}
