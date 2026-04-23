<?php

namespace App\Services\Shift;

use App\Models\Attendance;
use App\Models\Shift\AttendanceException;
use App\Models\Shift\Shift;
use App\Models\User;
use Carbon\Carbon;

class AttendanceValidationService
{
    public function __construct(private readonly ShiftService $shiftSvc) {}

    /**
     * Validate one attendance record against its assigned shift.
     * Idempotent: calling twice produces the same set of exceptions.
     *
     * @return AttendanceException[]
     */
    public function validate(Attendance $attendance): array
    {
        $user  = $attendance->user ?? User::find($attendance->user_id);
        $date  = Carbon::parse($attendance->date);
        $shift = $this->shiftSvc->getShiftForDate($user, $date);

        if (!$shift) {
            return []; // free-clock day — nothing to validate
        }

        $attendance->update(['shift_id' => $shift->id, 'is_shift_day' => true]);

        $expectedStart = $shift->expectedStartForDate($date);
        $expectedEnd   = $shift->expectedEndForDate($date);
        $exceptions    = [];

        // ── Absent (no check-in on a shift day) ───────────────────────────────
        if (!$attendance->check_in) {
            $attendance->update(['status' => 'absent', 'validated_at' => now()]);
            $exceptions[] = $this->upsertException($attendance, $shift, 'absent', [
                'expected_start' => $expectedStart,
                'expected_end'   => $expectedEnd,
            ]);
            return $exceptions;
        }

        $checkIn = $this->resolveDateTime($date, $attendance->check_in);

        // ── Check-in validation ────────────────────────────────────────────────
        if ($shift->isFlexible()) {
            $windowEnd = Carbon::parse($attendance->date . ' ' . $shift->flexible_window_end);
            if ($checkIn->gt($windowEnd)) {
                $exceptions[] = $this->upsertException($attendance, $shift, 'late_arrival', [
                    'expected_start'    => $windowEnd,
                    'actual_start'      => $checkIn,
                    'deviation_minutes' => (int) $windowEnd->diffInMinutes($checkIn),
                ]);
            }
        } else {
            $lateThreshold = $expectedStart->copy()->addMinutes($shift->grace_in_minutes);
            if ($checkIn->gt($lateThreshold)) {
                $exceptions[] = $this->upsertException($attendance, $shift, 'late_arrival', [
                    'expected_start'    => $expectedStart,
                    'actual_start'      => $checkIn,
                    'deviation_minutes' => (int) $expectedStart->diffInMinutes($checkIn),
                ]);
            }
        }

        // ── Check-out validation ───────────────────────────────────────────────
        if (!$attendance->check_out) {
            $exceptions[] = $this->upsertException($attendance, $shift, 'no_check_out', [
                'expected_end' => $expectedEnd,
                'actual_start' => $checkIn,
            ]);
            $attendance->update(['validated_at' => now()]);
            return $exceptions;
        }

        $checkOut = $this->resolveDateTime($date, $attendance->check_out, $checkIn);

        // Worked & overtime minutes
        $workedMinutes   = max(0, (int) $checkIn->diffInMinutes($checkOut) - $shift->break_duration_minutes);
        $overtimeMinutes = 0;

        if ($checkOut->gt($expectedEnd->copy()->addMinutes($shift->overtime_threshold_minutes))) {
            $overtimeMinutes = (int) $expectedEnd->diffInMinutes($checkOut);
            $exceptions[] = $this->upsertException($attendance, $shift, 'overtime', [
                'expected_end'     => $expectedEnd,
                'actual_end'       => $checkOut,
                'overtime_minutes' => $overtimeMinutes,
            ]);
        }

        // Early departure
        $earlyThreshold = $expectedEnd->copy()->subMinutes($shift->grace_out_minutes);
        if ($checkOut->lt($earlyThreshold)) {
            $exceptions[] = $this->upsertException($attendance, $shift, 'early_departure', [
                'expected_end'      => $expectedEnd,
                'actual_end'        => $checkOut,
                'deviation_minutes' => (int) $checkOut->diffInMinutes($expectedEnd),
            ]);
        }

        // Half day (worked less than full-day threshold but more than half-day threshold)
        $halfMin = $shift->half_day_threshold_hours * 60;
        $fullMin = $shift->min_hours_for_full_day * 60;
        if ($workedMinutes >= $halfMin && $workedMinutes < $fullMin) {
            $exceptions[] = $this->upsertException($attendance, $shift, 'half_day', [
                'expected_start'    => $expectedStart,
                'expected_end'      => $expectedEnd,
                'actual_start'      => $checkIn,
                'actual_end'        => $checkOut,
                'deviation_minutes' => $fullMin - $workedMinutes,
            ]);
        }

        $attendance->update([
            'worked_minutes'   => $workedMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'validated_at'     => now(),
        ]);

        return $exceptions;
    }

    /**
     * Detect absentees for a date: users assigned to a shift who have no attendance row.
     * Typically run as a scheduled job at end-of-day.
     */
    public function detectAbsentees(Carbon $date): int
    {
        $count = 0;

        User::active()->excludeAdmin()->each(function (User $user) use ($date, &$count) {
            $shift = $this->shiftSvc->getShiftForDate($user, $date);
            if (!$shift) {
                return;
            }

            $attendance = Attendance::firstOrCreate(
                ['user_id' => $user->id, 'date' => $date->toDateString()],
                ['shift_id' => $shift->id, 'is_shift_day' => true, 'status' => 'absent']
            );

            if (!$attendance->check_in) {
                AttendanceException::updateOrCreate(
                    ['attendance_id' => $attendance->id, 'exception_type' => 'absent'],
                    [
                        'user_id'        => $user->id,
                        'shift_id'       => $shift->id,
                        'date'           => $date->toDateString(),
                        'expected_start' => $shift->expectedStartForDate($date),
                        'expected_end'   => $shift->expectedEndForDate($date),
                        'status'         => 'pending',
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveDateTime(Carbon $date, string $timeStr, ?Carbon $checkIn = null): Carbon
    {
        $dt = $date->copy()->setTimeFromTimeString($timeStr);
        // If time looks earlier than check-in (night shift crossing midnight)
        if ($checkIn && $dt->lt($checkIn)) {
            $dt->addDay();
        }
        return $dt;
    }

    private function upsertException(Attendance $attendance, Shift $shift, string $type, array $data): AttendanceException
    {
        return AttendanceException::updateOrCreate(
            ['attendance_id' => $attendance->id, 'exception_type' => $type],
            array_merge([
                'user_id'  => $attendance->user_id,
                'shift_id' => $shift->id,
                'date'     => $attendance->date,
                'status'   => 'pending',
            ], $data)
        );
    }
}
