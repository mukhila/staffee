<?php

namespace App\Http\Controllers\Admin\Shift;

use App\Http\Controllers\Controller;
use App\Models\Shift\Shift;
use App\Models\Shift\ShiftAssignment;
use App\Models\User;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftAssignmentController extends Controller
{
    public function __construct(private readonly ShiftService $shiftSvc) {}

    public function index(Request $request)
    {
        $assignments = ShiftAssignment::with('user.department', 'shift', 'assignedBy')
            ->when($request->shift_id, fn ($q) => $q->where('shift_id', $request->shift_id))
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->when($request->search,   fn ($q) => $q->whereHas('user', fn ($u) =>
                $u->where('name', 'like', "%{$request->search}%")
            ))
            ->latest('effective_from')
            ->paginate(20)
            ->withQueryString();

        $shifts = Shift::active()->orderBy('name')->get();

        return view('admin.shifts.assignments.index', compact('assignments', 'shifts'));
    }

    public function create()
    {
        $employees  = User::active()->excludeAdmin()->with('department')->orderBy('name')->get();
        $shifts     = Shift::active()->orderBy('name')->get();

        return view('admin.shifts.assignments.create', compact('employees', 'shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_ids'       => 'required|array|min:1',
            'user_ids.*'     => 'exists:users,id',
            'shift_id'       => 'required|exists:shifts,id',
            'effective_from' => 'required|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
            'notes'          => 'nullable|string|max:500',
        ]);

        $shift        = Shift::findOrFail($request->shift_id);
        $effectiveFrom= Carbon::parse($request->effective_from);
        $effectiveTo  = $request->effective_to ? Carbon::parse($request->effective_to) : null;

        $count = $this->shiftSvc->bulkAssign(
            $request->user_ids,
            $shift,
            $effectiveFrom,
            $effectiveTo,
            auth()->user(),
        );

        return redirect()->route('admin.shifts.assignments.index')
            ->with('success', "Shift assigned to {$count} employee(s).");
    }

    public function destroy(ShiftAssignment $assignment)
    {
        abort_if($assignment->status !== 'active', 422, 'Only active assignments can be cancelled.');
        $assignment->update(['status' => 'cancelled']);
        return back()->with('success', 'Assignment cancelled.');
    }
}
