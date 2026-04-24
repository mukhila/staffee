<?php

namespace App\Jobs\Leave;

use App\Services\Leave\LeaveAccrualService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonthlyLeaveAccrualJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Carbon $accrualDate) {}

    public function handle(LeaveAccrualService $accrualService): void
    {
        Log::info("MonthlyLeaveAccrualJob: running accrual for {$this->accrualDate->toDateString()}");

        $count = $accrualService->runAccrual($this->accrualDate);

        Log::info("MonthlyLeaveAccrualJob: {$count} accrual records created");
    }
}
