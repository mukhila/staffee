<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeLoan;
use App\Models\User;
use App\Services\Payroll\LoanService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
    ) {}

    /**
     * List all loans, filterable by user/status.
     */
    public function index(Request $request)
    {
        $query = EmployeeLoan::with('user')->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $loans = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.payroll.loans.index', compact('loans', 'users'));
    }

    /**
     * Show the form to create a new loan.
     */
    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.payroll.loans.create', compact('users'));
    }

    /**
     * Store a newly created loan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'                => 'required|integer|exists:users,id',
            'loan_type'              => 'required|string|max:80',
            'principal_amount'       => 'required|numeric|min:0.000001',
            'issued_date'            => 'required|date',
            'recovery_start_period'  => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'installment_amount'     => 'required|numeric|min:0.000001',
            'total_installments'     => 'required|integer|min:1',
            'notes'                  => 'nullable|string',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $loan = $this->loanService->createLoan($user, $validated, auth()->id());

        return redirect()
            ->route('admin.payroll.loans.show', $loan)
            ->with('success', 'Loan created successfully.');
    }

    /**
     * Show loan details with installment schedule.
     */
    public function show(EmployeeLoan $loan)
    {
        $loan->load(['user', 'installments']);

        return view('admin.payroll.loans.show', compact('loan'));
    }

    /**
     * Cancel an active loan.
     */
    public function cancel(EmployeeLoan $loan)
    {
        if (!in_array($loan->status, ['active', 'hold'])) {
            return back()->with('error', 'Only active or on-hold loans can be cancelled.');
        }

        $loan->update(['status' => 'cancelled']);

        return back()->with('success', 'Loan cancelled successfully.');
    }
}
