<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Shift\AttendanceValidationService;
use App\Services\Shift\GeofenceService;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly AttendanceValidationService $validationService,
        private readonly GeofenceService $geofence,
    ) {}

    public function checkIn(Request $request)
    {
        $user  = auth()->user();
        $today = Carbon::today();

        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->toDateString())
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'You have already checked in today.');
        }

        try {
            $location = $this->resolveLocation($request, 'check_in');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
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
        ] + $location);

        return redirect()->back()->with('success', 'Checked in successfully.');
    }

    public function checkOut(Request $request)
    {
        $user  = auth()->user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->toDateString())
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'You have not checked in today.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'You have already checked out today.');
        }

        try {
            $location = $this->resolveLocation($request, 'check_out');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $attendance->update([
            'check_out' => now()->format('H:i:s'),
        ] + $location);

        // Run shift validation immediately on check-out for shift days
        if ($attendance->is_shift_day) {
            $attendance->refresh();
            $this->validationService->validate($attendance);
        }

        return redirect()->back()->with('success', 'Checked out successfully.');
    }

    /**
     * When the office geofence is on, require a position inside it and return the
     * columns to store. When it is off, nothing is read from the request or stored.
     *
     * @throws InvalidArgumentException
     */
    private function resolveLocation(Request $request, string $prefix): array
    {
        if (!$this->geofence->isEnabled()) {
            return [];
        }

        $distance = $this->geofence->assertInside($request->input('latitude'), $request->input('longitude'));

        return [
            "{$prefix}_latitude"   => (float) $request->input('latitude'),
            "{$prefix}_longitude"  => (float) $request->input('longitude'),
            "{$prefix}_distance_m" => $distance,
        ];
    }
}
