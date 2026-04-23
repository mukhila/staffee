<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Shift\AttendanceValidationService;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly AttendanceValidationService $validationService,
    ) {}

    public function checkIn()
    {
        $user  = auth()->user();
        $today = Carbon::today();

        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'You have already checked in today.');
        }

        // Resolve the shift for today (null = free-clock / holiday)
        $shift = $this->shiftService->getShiftForDate($user, $today);

        Attendance::create([
            'user_id'      => $user->id,
            'date'         => $today->toDateString(),
            'check_in'     => now()->format('H:i:s'),
            'status'       => 'present',
            'shift_id'     => $shift?->id,
            'is_shift_day' => $shift !== null,
        ]);

        return redirect()->back()->with('success', 'Checked in successfully.');
    }

    public function checkOut()
    {
        $user  = auth()->user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'You have not checked in today.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'You have already checked out today.');
        }

        $attendance->update([
            'check_out' => now()->format('H:i:s'),
        ]);

        // Run shift validation immediately on check-out for shift days
        if ($attendance->is_shift_day) {
            $attendance->refresh();
            $this->validationService->validate($attendance);
        }

        return redirect()->back()->with('success', 'Checked out successfully.');
    }
}
