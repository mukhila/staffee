<?php

namespace App\Jobs;

use App\Models\Payroll\PayrollRun;
use App\Notifications\PayrollSlipReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPayrollNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $payrollRunId) {}

    public function handle(): void
    {
        $run = PayrollRun::with('slips.employee')->findOrFail($this->payrollRunId);

        foreach ($run->slips as $slip) {
            $slip->employee?->notify(new PayrollSlipReadyNotification($slip));
        }
    }
}
