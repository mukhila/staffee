<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeBenefitDeduction;
use App\Models\User;
use App\Services\Payroll\BenefitDeductionService;
use Illuminate\Http\Request;

class BenefitDeductionController extends Controller
{
    public function __construct(
        private readonly BenefitDeductionService $service,
    ) {}

    /**
     * List all benefit deductions, filterable by user/status.
     */
    public function index(Request $request)
    {
        $query = EmployeeBenefitDeduction::with('user')->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $deductions = $query->paginate(20)->withQueryString();
        $users      = User::orderBy('name')->get(['id', 'name']);

        return view('admin.payroll.benefit-deductions.index', compact('deductions', 'users'));
    }

    /**
     * Show the form to create a new benefit deduction.
     */
    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.payroll.benefit-deductions.create', compact('users'));
    }

    /**
     * Store a newly created benefit deduction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'        => 'required|integer|exists:users,id',
            'benefit_name'   => 'required|string|max:150',
            'benefit_type'   => 'required|in:insurance,transport,food_voucher,provident_fund,other',
            'amount'         => 'required|numeric|min:0.000001',
            'effective_from' => 'required|date',
            'effective_to'   => 'nullable|date',
            'frequency'      => 'required|in:monthly,quarterly,annual',
            'notes'          => 'nullable|string',
        ]);

        $user = User::findOrFail($validated['user_id']);

        try {
            $deduction = $this->service->createDeduction($user, $validated, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['effective_to' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.payroll.benefit-deductions.show', $deduction)
            ->with('success', 'Benefit deduction created successfully.');
    }

    /**
     * Show benefit deduction details.
     */
    public function show(EmployeeBenefitDeduction $deduction)
    {
        $deduction->load(['user', 'createdBy']);

        return view('admin.payroll.benefit-deductions.show', compact('deduction'));
    }

    /**
     * Pause an active benefit deduction.
     */
    public function pause(EmployeeBenefitDeduction $deduction)
    {
        if ($deduction->status !== 'active') {
            return back()->with('error', 'Only active deductions can be paused.');
        }

        $this->service->pause($deduction, auth()->id());

        return back()->with('success', 'Benefit deduction paused.');
    }

    /**
     * Resume a paused benefit deduction.
     */
    public function resume(EmployeeBenefitDeduction $deduction)
    {
        if ($deduction->status !== 'paused') {
            return back()->with('error', 'Only paused deductions can be resumed.');
        }

        $this->service->resume($deduction, auth()->id());

        return back()->with('success', 'Benefit deduction resumed.');
    }

    /**
     * Terminate a benefit deduction.
     */
    public function terminate(EmployeeBenefitDeduction $deduction)
    {
        if ($deduction->status === 'terminated') {
            return back()->with('error', 'Deduction is already terminated.');
        }

        $this->service->terminate($deduction, auth()->id());

        return back()->with('success', 'Benefit deduction terminated.');
    }
}
