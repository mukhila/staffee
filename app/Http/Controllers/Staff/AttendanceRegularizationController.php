<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRegularization;
use App\Services\Shift\RegularizationService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AttendanceRegularizationController extends Controller
{
    public function __construct(private readonly RegularizationService $service) {}

    public function index()
    {
        $requests = AttendanceRegularization::with('reviewer:id,name')
            ->where('user_id', auth()->id())
            ->orderByDesc('date')
            ->paginate(20);

        $maxAgeDays = RegularizationService::MAX_AGE_DAYS;

        return view('staff.attendance.regularizations', compact('requests', 'maxAgeDays'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                => 'required|date|before_or_equal:today',
            'requested_check_in'  => 'nullable|date_format:H:i',
            'requested_check_out' => 'nullable|date_format:H:i',
            'reason'              => 'required|string|max:500',
        ]);

        try {
            $this->service->submit($request->user(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('staff.attendance.regularizations.index')
            ->with('success', 'Regularisation request submitted.');
    }
}
