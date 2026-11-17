<?php

namespace App\Services\Shift;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Check-in and check-out, shared by the web dashboard and the API so both
 * follow the same rules (one record a day, office geofence, shift validation).
 */
class AttendanceClockService
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly AttendanceValidationService $validationService,
        private readonly GeofenceService $geofence,
    ) {}

    /**
     * @throws InvalidArgumentException with a message fit to show the employee
     */
    public function checkIn(User $user, mixed $latitude = null, mixed $longitude = null): Attendance
    {
        $today = Carbon::today();

        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->toDateString())
            ->exists();

        if ($existing) {
            throw new InvalidArgumentException('You have already checked in today.');
        }

        $location = $this->resolveLocation('check_in', $latitude, $longitude);

        // Resolve the shift for today (null = free-clock / holiday)
        $shift = $this->shiftService->getShiftForDate($user, $today);

        return Attendance::create([
            'user_id'      => $user->id,
            'date'         => $today->toDateString(),
            'check_in'     => now()->format('H:i:s'),
            'status'       => 'present',
            'shift_id'     => $shift?->id,
            'is_shift_day' => $shift !== null,
        ] + $location);
    }

    /**
     * @throws InvalidArgumentException with a message fit to show the employee
     */
    public function checkOut(User $user, mixed $latitude = null, mixed $longitude = null): Attendance
    {
        $attendance = $this->today($user);

        if (!$attendance || !$attendance->check_in) {
            throw new InvalidArgumentException('You have not checked in today.');
        }

        if ($attendance->check_out) {
            throw new InvalidArgumentException('You have already checked out today.');
        }

        $location = $this->resolveLocation('check_out', $latitude, $longitude);

        $attendance->update(['check_out' => now()->format('H:i:s')] + $location);

        // Run shift validation immediately on check-out for shift days
        if ($attendance->is_shift_day) {
            $attendance->refresh();
            $this->validationService->validate($attendance);
        }

        return $attendance->fresh();
    }

    public function today(User $user): ?Attendance
    {
        return Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today()->toDateString())
            ->first();
    }

    public function geofenceRequired(): bool
    {
        return $this->geofence->isEnabled();
    }

    /**
     * When the office geofence is on, require a position inside it and return the
     * columns to store. When it is off, nothing is read or stored.
     *
     * @throws InvalidArgumentException
     */
    private function resolveLocation(string $prefix, mixed $latitude, mixed $longitude): array
    {
        if (!$this->geofence->isEnabled()) {
            return [];
        }

        $distance = $this->geofence->assertInside($latitude, $longitude);

        return [
            "{$prefix}_latitude"   => (float) $latitude,
            "{$prefix}_longitude"  => (float) $longitude,
            "{$prefix}_distance_m" => $distance,
        ];
    }
}
