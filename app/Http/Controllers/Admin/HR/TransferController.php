<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HR\LifecycleEvent;
use App\Models\HR\TransferRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-staff');

        $transfers = TransferRequest::with([
                'employee.department', 'requestedBy', 'fromDepartment', 'toDepartment', 'approver',
            ])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->department, fn ($q) => $q->where('to_department_id', $request->department))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.hr.transfers.index', compact('transfers', 'departments'));
    }

    public function create(Request $request)
    {
        $this->authorize('edit-staff');

        $employees   = User::active()->excludeAdmin()->with('department')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles       = Role::where('is_active', true)->orderBy('name')->get();
        $managers    = User::active()->excludeAdmin()->orderBy('name')->get();
        $preselect   = $request->employee ? User::find($request->employee) : null;

        return view('admin.hr.transfers.create', compact('employees', 'departments', 'roles', 'managers', 'preselect'));
    }

    public function store(Request $request)
    {
        $this->authorize('edit-staff');

        $validated = $request->validate([
            'user_id'            => 'required|exists:users,id',
            'to_department_id'   => 'required|exists:departments,id',
            'to_role'            => 'nullable|string|max:100',
            'to_designation'     => 'nullable|string|max:255',
            'to_reporting_to'    => 'nullable|exists:users,id',
            'effective_date'     => 'required|date|after_or_equal:today',
            'reason'             => 'required|string|max:2000',
        ]);

        $employee = User::with('department')->findOrFail($validated['user_id']);

        TransferRequest::create(array_merge($validated, [
            'requested_by'      => auth()->id(),
            'from_department_id'=> $employee->department_id,
            'from_role'         => $employee->role,
            'from_designation'  => $employee->designation,
            'from_reporting_to' => $employee->reporting_to,
            'status'            => 'pending',
        ]));

        return redirect()->route('admin.hr.transfers.index')
            ->with('success', 'Transfer request created and awaiting approval.');
    }

    public function show(TransferRequest $transfer)
    {
        $this->authorize('view-staff');
        $transfer->load(['employee.department', 'requestedBy', 'fromDepartment', 'toDepartment', 'approver']);
        return view('admin.hr.transfers.show', compact('transfer'));
    }

    public function approve(Request $request, TransferRequest $transfer)
    {
        $this->authorize('edit-staff');
        abort_if($transfer->status !== 'pending', 422, 'Transfer is no longer pending.');

        $request->validate(['notes' => 'nullable|string|max:500']);

        DB::transaction(function () use ($request, $transfer) {
            $transfer->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes'       => $request->notes,
            ]);

            // Apply transfer to employee record
            $transfer->employee->update(array_filter([
                'department_id' => $transfer->to_department_id,
                'role'          => $transfer->to_role         ?: $transfer->employee->role,
                'designation'   => $transfer->to_designation  ?: $transfer->employee->designation,
                'reporting_to'  => $transfer->to_reporting_to ?: $transfer->employee->reporting_to,
            ]));

            // Lifecycle event
            LifecycleEvent::create([
                'user_id'        => $transfer->user_id,
                'event_type'     => 'transfer',
                'effective_date' => $transfer->effective_date,
                'description'    => "Transferred from {$transfer->fromDepartment?->name} to {$transfer->toDepartment?->name}",
                'created_by'     => auth()->id(),
                'is_visible'     => true,
            ]);
        });

        return back()->with('success', 'Transfer approved and applied to employee record.');
    }

    public function reject(Request $request, TransferRequest $transfer)
    {
        $this->authorize('edit-staff');
        abort_if($transfer->status !== 'pending', 422, 'Transfer is no longer pending.');

        $request->validate(['notes' => 'required|string|max:500']);

        $transfer->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes'       => $request->notes,
        ]);

        return back()->with('success', 'Transfer request rejected.');
    }
}
