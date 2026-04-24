<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $types = LeaveType::withCount('policies')->orderBy('name')->get();
        return view('admin.leaves.types.index', compact('types'));
    }

    public function create()
    {
        $categories = LeaveType::CATEGORIES;
        return view('admin.leaves.types.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:20|unique:leave_types,code',
            'category'          => 'required|in:' . implode(',', array_keys(LeaveType::CATEGORIES)),
            'color'             => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'is_paid'           => 'boolean',
            'requires_approval' => 'boolean',
            'max_days_per_year' => 'nullable|integer|min:1|max:365',
            'allow_half_day'    => 'boolean',
            'requires_document' => 'boolean',
            'description'       => 'nullable|string|max:1000',
        ]);

        $data['is_paid']           = $request->boolean('is_paid');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['allow_half_day']    = $request->boolean('allow_half_day');
        $data['requires_document'] = $request->boolean('requires_document');

        LeaveType::create($data);
        return redirect()->route('admin.leaves.types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $type)
    {
        $categories = LeaveType::CATEGORIES;
        return view('admin.leaves.types.edit', compact('type', 'categories'));
    }

    public function update(Request $request, LeaveType $type)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:20|unique:leave_types,code,' . $type->id,
            'category'          => 'required|in:' . implode(',', array_keys(LeaveType::CATEGORIES)),
            'color'             => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'is_paid'           => 'boolean',
            'requires_approval' => 'boolean',
            'max_days_per_year' => 'nullable|integer|min:1|max:365',
            'allow_half_day'    => 'boolean',
            'requires_document' => 'boolean',
            'is_active'         => 'boolean',
            'description'       => 'nullable|string|max:1000',
        ]);

        $data['is_paid']           = $request->boolean('is_paid');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['allow_half_day']    = $request->boolean('allow_half_day');
        $data['requires_document'] = $request->boolean('requires_document');
        $data['is_active']         = $request->boolean('is_active', true);

        $type->update($data);
        return redirect()->route('admin.leaves.types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $type)
    {
        abort_if($type->requests()->exists(), 422, 'Cannot delete a type with existing requests.');
        $type->delete();
        return back()->with('success', 'Leave type deleted.');
    }
}
