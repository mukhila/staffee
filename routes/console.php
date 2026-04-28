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

// Monthly leave accrual: runs at 00:30 on the 1st of each month
Schedule::job(new \App\Jobs\Leave\MonthlyLeaveAccrualJob(Carbon::today()->startOfMonth()))
    ->monthlyOn(1, '00:30')
    ->name('monthly-leave-accrual')
    ->withoutOverlapping();

// Year-end carry-forward: runs at 23:00 on Dec 31
Schedule::job(new \App\Jobs\Leave\YearEndCarryForwardJob(Carbon::today()->year))
    ->yearlyOn(12, 31, '23:00')
    ->name('year-end-leave-carry-forward')
    ->withoutOverlapping();

// Low balance warning: runs on the 1st of each month
Schedule::job(new \App\Jobs\Leave\LowBalanceWarningJob())
    ->monthlyOn(1, '09:00')
    ->name('low-leave-balance-warning')
    ->withoutOverlapping();

// Daily leave reminders: runs every morning at 08:00
Schedule::job(new \App\Jobs\Leave\LeaveReminderJob())
    ->dailyAt('08:00')
    ->name('leave-reminder')
    ->withoutOverlapping();

// Idle threshold alerts: runs every 5 minutes during working hours
Schedule::command('monitoring:check-idle')
    ->everyFiveMinutes()
    ->name('monitoring-idle-check')
    ->withoutOverlapping();
