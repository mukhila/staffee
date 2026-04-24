<?php

namespace App\Jobs;

use App\Models\Payroll\PayrollRun;
use App\Services\Payroll\PayrollProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateTaxContributionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $payrollRunId) {}

    public function handle(PayrollProcessingService $service): void
    {
        $run = PayrollRun::with(['slips', 'calendar'])->findOrFail($this->payrollRunId);

        foreach ($run->slips as $slip) {
            $service->calculateTax($slip->gross_earnings, $slip->salaryStructure?->taxRegime?->brackets ?? collect());
        }
    }
}
