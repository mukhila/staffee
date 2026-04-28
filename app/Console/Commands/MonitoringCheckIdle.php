<?php

namespace App\Console\Commands;

use App\Models\Monitoring\MonitoringIdlePeriod;
use App\Models\Monitoring\MonitoringSetting;
use App\Models\Monitoring\MonitoringScreenshot;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonitoringCheckIdle extends Command
{
    protected $signature   = 'monitoring:check-idle';
    protected $description = 'Alert managers when a staff member has been idle beyond the configured threshold';

    public function handle(): void
    {
        $now = Carbon::now();

        // Only check within working hours (use global setting as default)
        $global = MonitoringSetting::whereNull('user_id')->first();

        $idlePeriods = MonitoringIdlePeriod::whereNull('idle_end')   // still idle
            ->where('idle_start', '<=', $now->copy()->subMinutes(1)) // at least 1 min old
            ->with('user')
            ->get();

        foreach ($idlePeriods as $period) {
            $user = $period->user;
            if (!$user) continue;

            // Per-user setting or global
            $setting = MonitoringSetting::where('user_id', $user->id)->first() ?? $global;
            if (!$setting || !$setting->enabled) continue;

            $thresholdMinutes = (int) ceil($setting->idle_threshold_seconds / 60);
            $idleMinutes      = (int) ceil($now->diffInSeconds($period->idle_start) / 60);

            if ($idleMinutes < $thresholdMinutes) continue;

            // Alert at exact threshold, then every threshold interval (avoid spam)
            $alertIntervals = [1]; // first alert
            for ($m = 2 * $thresholdMinutes; $m <= $idleMinutes; $m += $thresholdMinutes) {
                $alertIntervals[] = $m / $thresholdMinutes;
            }
            $bucket = (int) floor($idleMinutes / $thresholdMinutes);
            if ($bucket < 1) continue;

            // Check if we already notified for this exact bucket
            $alreadyNotified = Notification::where('type', 'idle_alert')
                ->where('message', 'LIKE', "%idle_period_id:{$period->id}:bucket:{$bucket}%")
                ->exists();

            if ($alreadyNotified) continue;

            // Find manager (reporting_to) or admin
            $managers = collect();
            if ($user->reporting_to) {
                $manager = User::find($user->reporting_to);
                if ($manager) $managers->push($manager);
            }
            if ($managers->isEmpty()) {
                $managers = User::where('role', 'admin')->get();
            }

            foreach ($managers as $manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'type'    => 'idle_alert',
                    'title'   => "Idle Alert: {$user->name}",
                    'message' => "{$user->name} has been idle for {$idleMinutes} minutes. [idle_period_id:{$period->id}:bucket:{$bucket}]",
                    'url'     => route('admin.monitoring.show', $user->id),
                ]);
            }

            $this->info("Alerted managers: {$user->name} idle {$idleMinutes} min (threshold {$thresholdMinutes} min).");
        }
    }
}
