<?php

namespace App\Console\Commands;

use App\Models\Monitoring\MonitoringActivityLog;
use App\Models\Monitoring\MonitoringIdlePeriod;
use App\Models\Monitoring\MonitoringScreenshot;
use App\Models\Monitoring\MonitoringSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeMonitoringData extends Command
{
    protected $signature   = 'monitoring:purge';
    protected $description = 'Delete monitoring screenshots, activity logs, and idle periods older than the configured retention window.';

    public function handle(): int
    {
        $global = MonitoringSetting::whereNull('user_id')->first();
        $retentionDays = (int) ($global?->retention_days ?? 0);

        if ($retentionDays === 0) {
            $this->info('Retention disabled (retention_days = 0). Nothing purged.');
            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays($retentionDays)->startOfDay();
        $this->info("Purging monitoring data captured before {$cutoff->toDateString()} ({$retentionDays}-day retention).");

        $disk = Storage::disk('local');

        // Screenshots — delete files then records, chunked
        $screenshotCount = 0;
        MonitoringScreenshot::where('captured_at', '<', $cutoff)
            ->where('is_flagged', false) // keep flagged/escalated for investigation
            ->where(function ($q) {
                $q->where('review_status', '!=', 'escalated')->orWhereNull('review_status');
            })
            ->chunkById(100, function ($screenshots) use ($disk, &$screenshotCount) {
                foreach ($screenshots as $screenshot) {
                    if ($screenshot->file_path && $disk->exists($screenshot->file_path)) {
                        $disk->delete($screenshot->file_path);
                    }
                    if ($screenshot->thumbnail_path && $disk->exists($screenshot->thumbnail_path)) {
                        $disk->delete($screenshot->thumbnail_path);
                    }
                    $screenshot->delete();
                    $screenshotCount++;
                }
            });

        // Activity logs
        $activityCount = MonitoringActivityLog::where('recorded_at', '<', $cutoff)->delete();

        // Idle periods
        $idleCount = MonitoringIdlePeriod::where('idle_end', '<', $cutoff)->delete();

        $summary = "Purge complete — screenshots: {$screenshotCount}, activity logs: {$activityCount}, idle periods: {$idleCount}.";
        $this->info($summary);
        Log::info('[monitoring:purge] ' . $summary);

        return self::SUCCESS;
    }
}
