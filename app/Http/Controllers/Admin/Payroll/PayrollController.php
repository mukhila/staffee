<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Jobs\SendPayrollNotificationJob;
use App\Models\Payroll\PayrollAdjustment;
use App\Models\Payroll\PayrollRun;
use App\Models\Payroll\PayrollSlip;
use App\Notifications\PayrollProcessedNotification;
use App\Services\Payroll\PayrollProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollProcessingService $processingService,
    ) {}

    public function dashboard()
    {
        $this->authorize('view-staff');

        $month = now()->month;
        $year  = now()->year;

        // Current month run
        $currentRun = PayrollRun::forPeriod($month, $year)->latest()->first();

        // Last 6 months outflow (net_pay sum per month)
        $outflowTrend = PayrollSlip::selectRaw('for_month, for_year, SUM(net_pay) as total_net, COUNT(*) as headcount')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_slips.payroll_run_id')
            ->whereIn('payroll_slips.status', ['published'])
            ->where(function ($q) use ($month, $year) {
                $q->whereRaw('(payroll_runs.for_year * 12 + payroll_runs.for_month) >= ?', [($year * 12 + $month) - 5])
                  ->whereRaw('(payroll_runs.for_year * 12 + payroll_runs.for_month) <= ?', [$year * 12 + $month]);
            })
            ->groupBy('for_year', 'for_month')
            ->orderBy('for_year')->orderBy('for_month')
            ->get();

        // Department breakdown for current month
        $deptBreakdown = PayrollSlip::selectRaw('departments.name as dept_name, SUM(payroll_slips.net_pay) as total_net, COUNT(*) as headcount')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_slips.payroll_run_id')
            ->join('users', 'users.id', '=', 'payroll_slips.user_id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->where('payroll_runs.for_month', $month)
            ->where('payroll_runs.for_year', $year)
            ->whereIn('payroll_slips.status', ['published'])
            ->groupBy('departments.name')
            ->orderByDesc('total_net')
            ->limit(10)
            ->get();

        $pendingAdjustments = PayrollAdjustment::with(['employee', 'definition'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        $runStats = [
            'draft'     => PayrollRun::where('status', 'draft')->count(),
            'completed' => PayrollRun::completed()->count(),
            'pending'   => PayrollAdjustment::where('status', 'pending')->count(),
        ];

        return view('admin.payroll.dashboard', compact(
            'currentRun', 'outflowTrend', 'deptBreakdown', 'pendingAdjustments', 'runStats', 'month', 'year'
        ));
    }

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
