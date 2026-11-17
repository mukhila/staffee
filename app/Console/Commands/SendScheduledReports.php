<?php

namespace App\Console\Commands;

use App\Models\ReportSubscription;
use App\Notifications\ScheduledReportNotification;
use App\Services\Reports\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled
                            {--date= : Pretend today is this date (YYYY-MM-DD)}
                            {--dry-run : List what would be sent without sending}';

    protected $description = 'Email the weekly and monthly reports people have subscribed to';

    public function handle(ReportService $reports): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::today();
        $sent = $skipped = $failed = 0;

        ReportSubscription::with('user')
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($reports, $today, &$sent, &$skipped, &$failed) {
                foreach ($subscriptions as $sub) {
                    if (!$sub->isDue($today)) {
                        continue;
                    }

                    $user = $sub->user;

                    // Access is checked at every send: if someone lost the permission
                    // or left, they stop receiving the report.
                    try {
                        $report = $reports->find($sub->report);
                    } catch (Throwable) {
                        $sub->update(['is_active' => false, 'last_error' => 'This report no longer exists.']);
                        $skipped++;
                        continue;
                    }

                    if (!$user || !$user->is_active || !$report->canView($user)) {
                        $sub->update(['is_active' => false, 'last_error' => 'Turned off: no longer allowed to see this report.']);
                        $skipped++;
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("Would send {$report->label()} to {$user->email}");
                        $sent++;
                        continue;
                    }

                    try {
                        [$from, $to, $range] = $reports->period($sub->period(), $today);
                        $built = $reports->build($report, $user, $from, $to, $range, $sub->department_id);

                        $user->notify(new ScheduledReportNotification($built));

                        // With --date, record that day so a re-run for the same day does not resend.
                        $sub->update(['last_sent_at' => $this->option('date') ? $today->copy()->setTime(7, 0) : now(), 'last_error' => null]);
                        $sent++;
                    } catch (Throwable $e) {
                        report($e);
                        $sub->update(['last_error' => mb_substr('Could not send: ' . $e->getMessage(), 0, 255)]);
                        $failed++;
                    }
                }
            });

        $this->info("Scheduled reports for {$today->toDateString()}: {$sent} sent, {$skipped} turned off, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
