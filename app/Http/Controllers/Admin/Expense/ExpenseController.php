<?php

namespace App\Http\Controllers\Admin\Expense;

use App\Http\Controllers\Controller;
use App\Models\Expense\ExpenseClaim;
use App\Models\User;
use App\Services\Expense\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenseService) {}

    public function index(Request $request)
    {
        $claims = ExpenseClaim::with(['user', 'category', 'project'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $users    = User::orderBy('name')->get();
        $statuses = ['draft', 'submitted', 'approved', 'rejected', 'paid'];

        return view('admin.expenses.index', compact('claims', 'users', 'statuses'));
    }

    public function show(ExpenseClaim $claim)
    {
        $claim->load(['user', 'category', 'project', 'reviewer']);

        return view('admin.expenses.show', compact('claim'));
    }

    public function approve(Request $request, ExpenseClaim $claim)
    {
        $request->validate([
            'review_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->expenseService->approve($claim, auth()->id(), $request->review_notes);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Expense claim approved.');
    }

    public function reject(Request $request, ExpenseClaim $claim)
    {
        $request->validate([
            'review_notes' => 'required|string|max:2000',
        ]);

        try {
            $this->expenseService->reject($claim, auth()->id(), $request->review_notes);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Expense claim rejected.');
    }

    public function markPaid(ExpenseClaim $claim)
    {
        try {
            $this->expenseService->markPaid($claim, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Expense claim marked as paid.');
    }
}
