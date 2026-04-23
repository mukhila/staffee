<?php

namespace App\Http\Controllers\Admin\Shift;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift\AttendanceException;
use App\Services\Shift\AttendanceValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceExceptionController extends Controller
{
    public function __construct(private readonly AttendanceValidationService $validator) {}

    public function index(Request $request)
    {
        $exceptions = AttendanceException::with('user.department', 'shift', 'reviewedBy')
            ->when($request->type,   fn ($q) => $q->where('exception_type', $request->type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->date,   fn ($q) => $q->where('date', $request->date))
            ->when($request->search, fn ($q) => $q->whereHas('user', fn ($u) =>
                $u->where('name', 'like', "%{$request->search}%")
            ))
            ->orderByDesc('date')
            ->paginate(25)
            ->withQueryString();

        $types = AttendanceException::TYPES;

        return view('admin.shifts.exceptions.index', compact('exceptions', 'types'));
    }

    public function approve(Request $request, AttendanceException $exception)
    {
        abort_if(!$exception->isPending(), 422, 'Already actioned.');
        $exception->approve(auth()->user(), $request->manager_notes);
        return back()->with('success', 'Exception approved.');
    }

    public function reject(Request $request, AttendanceException $exception)
    {
        abort_if(!$exception->isPending(), 422, 'Already actioned.');
        $request->validate(['manager_notes' => 'required|string|max:500']);
        $exception->reject(auth()->user(), $request->manager_notes);
        return back()->with('success', 'Exception rejected.');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:attendance_exceptions,id']);
        $user = auth()->user();
        AttendanceException::whereIn('id', $request->ids)->pending()->each->approve($user);
        return back()->with('success', count($request->ids) . ' exceptions approved.');
    }

    /**
     * Manually trigger attendance validation for a specific date.
     * Useful for back-filling or re-running validation after shift changes.
     */
    public function validateDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date  = Carbon::parse($request->date);
        $count = 0;

        Attendance::where('date', $date->toDateString())->with('user')->each(function ($attendance) use (&$count) {
            $this->validator->validate($attendance);
            $count++;
        });

        $absences = $this->validator->detectAbsentees($date);

        return back()->with('success', "Validated {$count} attendance records, detected {$absences} absent.");
    }
}
