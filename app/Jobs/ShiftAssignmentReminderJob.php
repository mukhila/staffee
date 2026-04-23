<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ShiftAssignedNotification;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ShiftAssignmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ShiftService $shiftService): void
    {
        $tomorrow = Carbon::tomorrow();

        Log::info("ShiftAssignmentReminderJob: sending reminders for {$tomorrow->toDateString()}");

        $notified = 0;

        User::active()->excludeAdmin()->with('manager')->each(function (User $user) use ($shiftService, $tomorrow, &$notified) {
            $assignment = $shiftService->getAssignmentForDate($user, $tomorrow);
            if (!$assignment) {
                return;
            }

            $shift = $shiftService->getShiftForDate($user, $tomorrow);
            if (!$shift) {
                return; // holiday or rotating day-off
            }

            try {
                $user->notify(new ShiftAssignedNotification($assignment, isReminder: true));
                $notified++;
            } catch (\Throwable $e) {
                Log::error("ShiftAssignmentReminderJob: failed for user #{$user->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        });

        Log::info("ShiftAssignmentReminderJob: {$notified} reminders dispatched");
    }
}
