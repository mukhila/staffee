<?php

use App\Jobs\DailyAttendanceValidationJob;
use App\Jobs\ShiftAssignmentReminderJob;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run at 23:45 every day: validate all attendance records and detect absentees
Schedule::job(new DailyAttendanceValidationJob(Carbon::today()))
    ->dailyAt('23:45')
    ->name('daily-attendance-validation')
    ->withoutOverlapping();

// Run at 18:00 every weekday: remind employees of tomorrow's shift
Schedule::job(new ShiftAssignmentReminderJob())
    ->weekdays()
    ->at('18:00')
    ->name('shift-assignment-reminder')
    ->withoutOverlapping();
