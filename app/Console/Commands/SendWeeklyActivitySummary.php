<?php

namespace App\Console\Commands;

use App\Models\Monitoring\MonitoringSetting;
use App\Models\User;
use App\Notifications\WeeklyActivitySummaryNotification;
use App\Services\Monitoring\ProductivityService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendWeeklyActivitySummary extends Command
{
    protected $signature = 'monitoring:weekly-summary
                            {--week= : Any date in the week to summarise (default: last week)}
                            {--dry-run : List who would receive a summary without sending}';

    protected $description = 'Send each monitored employee a summary of their own activity for the past week';

    public function handle(ProductivityService $productivity): int
    {
        $weekStart = $this->option('week')
            ? Carbon::parse($this->option('week'))->startOfWeek()
            : now()->subWeek()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $sent = 0;

        User::where('is_active', true)->orderBy('id')->chunkById(100, function ($users) use (
            $productivity, $weekStart, $weekEnd, &$sent
        ) {
            foreach ($users as $user) {
                if (!MonitoringSetting::forUser($user)->enabled) {
                    continue;
                }

                $summary = $productivity->summarise($user->id, $weekStart, $weekEnd);

                // Nothing recorded (on leave, not monitored that week): no email.
                if ($summary['active_seconds'] === 0) {
                    continue;
                }

                $summary['top_apps'] = $summary['top_apps']->all();

                if ($this->option('dry-run')) {
                    $this->line("Would send to {$user->email}");
                } else {
                    $user->notify(new WeeklyActivitySummaryNotification(
                        $summary, $weekStart->toDateString(), $weekEnd->toDateString()
                    ));
                }

                $sent++;
            }
        });

        $verb = $this->option('dry-run') ? 'would be sent' : 'sent';
        $this->info("{$sent} weekly activity summaries {$verb} for {$weekStart->toDateString()} to {$weekEnd->toDateString()}.");

        return self::SUCCESS;
    }
}
