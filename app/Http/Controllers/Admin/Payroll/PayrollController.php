<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Jobs\SendPayrollNotificationJob;
use App\Models\Payroll\PayrollRun;
use App\Notifications\PayrollProcessedNotification;
use App\Services\Payroll\PayrollProcessingService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollProcessingService $processingService,
    ) {}

    public function index()
    {
        $runs = PayrollRun::withCount('slips')->latest()->paginate(20);

        return view('admin.payroll.runs.index', compact('runs'));
    }

    public function initiateRun(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $run = $this->processingService->processPayroll($validated['month'], $validated['year']);

        return redirect()->route('admin.payroll.runs.status', $run)
            ->with('success', 'Payroll run initiated successfully.');
    }

    public function processPayroll(PayrollRun $payrollRun)
    {
        $run = $this->processingService->processRun($payrollRun->load('calendar'));

        if (auth()->user()) {
            auth()->user()->notify(new PayrollProcessedNotification($run));
        }

        return redirect()->route('admin.payroll.runs.status', $run)
            ->with('success', 'Payroll processed successfully.');
    }

    public function publishSlips(PayrollRun $payrollRun)
    {
        $run = $this->processingService->publishPayrollRun($payrollRun->load('slips.employee'));
        SendPayrollNotificationJob::dispatch($run->id);

        return redirect()->route('admin.payroll.runs.status', $run)
            ->with('success', 'Payroll slips published successfully.');
    }

    public function viewStatus(PayrollRun $payrollRun)
    {
        $payrollRun->load(['slips.employee', 'runEmployees', 'calendar']);

        return view('admin.payroll.runs.status', compact('payrollRun'));
    }
}
