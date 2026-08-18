<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Expense\ExpenseCategory;
use App\Models\Expense\ExpenseClaim;
use App\Models\Project;
use App\Services\Expense\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenseService) {}

    public function index()
    {
        $claims = ExpenseClaim::with(['category', 'project'])
            ->forUser(auth()->id())
            ->latest()
            ->paginate(20);

        return view('staff.expenses.index', compact('claims'));
    }

    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        $projects   = Project::orderBy('name')->get();

        return view('staff.expenses.create', compact('categories', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:200',
            'description'         => 'nullable|string',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => 'required|string|size:3',
            'expense_date'        => 'required|date',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'project_id'          => 'nullable|exists:projects,id',
            'receipt_path'        => 'nullable|string|max:400',
            'action'              => 'required|in:draft,submit',
        ]);

        $status = $validated['action'] === 'submit' ? 'submitted' : 'draft';

        $claim = ExpenseClaim::create([
            'user_id'             => auth()->id(),
            'title'               => $validated['title'],
            'description'         => $validated['description'] ?? null,
            'amount'              => $validated['amount'],
            'currency'            => $validated['currency'],
            'expense_date'        => $validated['expense_date'],
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'project_id'          => $validated['project_id'] ?? null,
            'receipt_path'        => $validated['receipt_path'] ?? null,
            'status'              => $status,
            'submitted_at'        => $status === 'submitted' ? now() : null,
        ]);

        return redirect()->route('staff.expenses.index')
            ->with('success', 'Expense claim ' . ($status === 'submitted' ? 'submitted' : 'saved as draft') . ' successfully.');
    }

    public function show(ExpenseClaim $claim)
    {
        abort_unless($claim->user_id === auth()->id(), 403);

        $claim->load(['category', 'project', 'reviewer']);

        return view('staff.expenses.show', compact('claim'));
    }

    public function destroy(ExpenseClaim $claim)
    {
        abort_unless($claim->user_id === auth()->id(), 403);

        if ($claim->status !== 'draft') {
            return back()->with('error', 'Only draft claims can be deleted.');
        }

        $claim->delete();

        return redirect()->route('staff.expenses.index')
            ->with('success', 'Expense claim deleted.');
    }

    public function submit(ExpenseClaim $claim)
    {
        abort_unless($claim->user_id === auth()->id(), 403);

        try {
            $this->expenseService->submit($claim, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Expense claim submitted for approval.');
    }
}
