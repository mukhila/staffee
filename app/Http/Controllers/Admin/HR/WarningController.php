<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\WarningRecord;
use App\Models\User;
use Illuminate\Http\Request;

class WarningController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-staff');

        $warnings = WarningRecord::with(['employee', 'issuedBy'])
            ->when($request->employee, fn ($q) => $q->where('user_id', $request->employee))
            ->when($request->type,     fn ($q) => $q->where('warning_type', $request->type))
            ->when($request->status === 'active',   fn ($q) => $q->whereNull('resolved_at'))
            ->when($request->status === 'resolved', fn ($q) => $q->whereNotNull('resolved_at'))
            ->latest('incident_date')
            ->paginate(20)
            ->withQueryString();

        $employees = User::active()->excludeAdmin()->orderBy('name')->get();

        return view('admin.hr.warnings.index', compact('warnings', 'employees'));
    }

    public function create(Request $request)
    {
        $this->authorize('edit-staff');

        $employees = User::active()->excludeAdmin()->with('department')->orderBy('name')->get();
        $preselect = $request->employee ? User::find($request->employee) : null;

        return view('admin.hr.warnings.create', compact('employees', 'preselect'));
    }

    public function store(Request $request)
    {
        $this->authorize('edit-staff');

        $validated = $request->validate([
            'user_id'           => 'required|exists:users,id',
            'warning_type'      => 'required|in:verbal,written,final_written,suspension,pip',
            'incident_date'     => 'required|date|before_or_equal:today',
            'response_deadline' => 'nullable|date|after:incident_date',
            'description'       => 'required|string|max:5000',
            'improvement_plan'  => 'nullable|string|max:5000',
        ]);

        WarningRecord::create(array_merge($validated, [
            'issued_by'      => auth()->id(),
            'is_acknowledged'=> false,
        ]));

        return redirect()->route('admin.hr.warnings.index')
            ->with('success', 'Warning record created successfully.');
    }

    public function show(WarningRecord $warning)
    {
        $this->authorize('view-staff');
        $warning->load(['employee.department', 'issuedBy', 'resolvedBy']);
        return view('admin.hr.warnings.show', compact('warning'));
    }

    public function destroy(WarningRecord $warning)
    {
        $this->authorize('edit-staff');
        $warning->delete();
        return redirect()->route('admin.hr.warnings.index')
            ->with('success', 'Warning record deleted.');
    }

    public function resolve(Request $request, WarningRecord $warning)
    {
        $this->authorize('edit-staff');
        abort_if($warning->isResolved(), 422, 'This warning is already resolved.');

        $request->validate(['resolution_notes' => 'nullable|string|max:2000']);

        $warning->update([
            'resolved_at'       => now(),
            'resolved_by'       => auth()->id(),
            'resolution_notes'  => $request->resolution_notes,
        ]);

        return back()->with('success', 'Warning marked as resolved.');
    }

    public function acknowledge(Request $request, WarningRecord $warning)
    {
        $this->authorize('edit-staff');
        abort_if($warning->is_acknowledged, 422, 'Already acknowledged.');

        $warning->update([
            'is_acknowledged'   => true,
            'acknowledged_at'   => now(),
        ]);

        return back()->with('success', 'Warning acknowledged.');
    }
}
