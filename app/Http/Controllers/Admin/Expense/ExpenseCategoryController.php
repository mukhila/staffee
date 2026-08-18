<?php

namespace App\Http\Controllers\Admin\Expense;

use App\Http\Controllers\Controller;
use App\Models\Expense\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('claims')->orderBy('name')->paginate(20);

        return view('admin.expense-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string',
            'requires_receipt' => 'boolean',
        ]);

        ExpenseCategory::create($validated);

        return back()->with('success', 'Expense category created.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string',
            'requires_receipt' => 'boolean',
        ]);

        $expenseCategory->update($validated);

        return back()->with('success', 'Expense category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();

        return back()->with('success', 'Expense category deleted.');
    }
}
