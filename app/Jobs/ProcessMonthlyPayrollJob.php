<?php

namespace App\Jobs;

use App\Services\Payroll\PayrollProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMonthlyPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function handle(PayrollProcessingService $service): void
    {
        $service->processPayroll($this->month, $this->year);
    }
}
