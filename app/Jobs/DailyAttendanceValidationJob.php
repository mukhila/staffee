<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Shift\AttendanceException;
use App\Models\User;
use App\Notifications\ExceptionMarkedNotification;
use App\Services\Shift\AttendanceValidationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DailyAttendanceValidationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Carbon $date) {}

    public function handle(AttendanceValidationService $validationService): void
    {
        $date = $this->date;

        Log::info("DailyAttendanceValidationJob: running for {$date->toDateString()}");

        // 1. Detect employees who should have worked but have no attendance row
        $absentCount = $validationService->detectAbsentees($date);
        Log::info("DailyAttendanceValidationJob: {$absentCount} absentees detected");

        // 2. Validate all unvalidated attendance records for the day
        $records = Attendance::whereDate('date', $date->toDateString())
            ->whereNull('validated_at')
            ->with('user')
            ->get();

        $exceptionCount = 0;

        foreach ($records as $attendance) {
            try {
                $exceptions = $validationService->validate($attendance);
                $exceptionCount += count($exceptions);

                // Notify the employee's manager for each new pending exception
                foreach ($exceptions as $exception) {
                    $this->notifyManager($attendance->user, $exception);
                }
            } catch (\Throwable $e) {
                Log::error("DailyAttendanceValidationJob: failed for attendance #{$attendance->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("DailyAttendanceValidationJob: {$exceptionCount} exceptions created/updated for {$date->toDateString()}");
    }

    private function notifyManager(User $employee, AttendanceException $exception): void
    {
        $manager = $employee->manager;
        if ($manager) {
            $manager->notify(new ExceptionMarkedNotification($exception, $employee));
        }
    }
}
