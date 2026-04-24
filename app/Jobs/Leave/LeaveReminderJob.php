<?php

namespace App\Jobs\Leave;

use App\Models\LeaveRequest;
use App\Notifications\Leave\LeaveEndReminderNotification;
use App\Notifications\Leave\LeaveStartReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LeaveReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $tomorrow = now()->addDay()->toDateString();
        $today    = now()->toDateString();

        // Remind employees whose approved leave starts tomorrow
        $startCount = LeaveRequest::approved()
            ->whereDate('from_date', $tomorrow)
            ->with('user', 'leaveType')
            ->get()
            ->each(fn ($leave) => $leave->user->notify(new LeaveStartReminderNotification($leave)))
            ->count();

        // Remind employees whose approved leave ends today (return to work tomorrow)
        $endCount = LeaveRequest::approved()
            ->whereDate('to_date', $today)
            ->with('user', 'leaveType')
            ->get()
            ->each(fn ($leave) => $leave->user->notify(new LeaveEndReminderNotification($leave)))
            ->count();

        Log::info("LeaveReminderJob: {$startCount} start reminders, {$endCount} end reminders sent");
    }
}
