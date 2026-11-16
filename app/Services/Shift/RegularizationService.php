<?php

namespace App\Services\Shift;

use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Staff ask for a missed or wrong punch to be corrected; an approver applies it.
 */
class RegularizationService
{
    /** Requests older than this many days are refused. */
    public const MAX_AGE_DAYS = 30;

    public function __construct(private readonly AttendanceValidationService $validationSvc) {}

    /**
     * @param  array{date:string, requested_check_in?:?string, requested_check_out?:?string, reason:string}  $data
     *
     * @throws InvalidArgumentException
     */
    public function submit(User $employee, array $data): AttendanceRegularization
    {
        $date = Carbon::parse($data['date'])->startOfDay();
        $in   = $data['requested_check_in'] ?? null;
        $out  = $data['requested_check_out'] ?? null;

        if ($date->isFuture()) {
            throw new InvalidArgumentException('You cannot regularise a future date.');
        }

        if ($date->lt(Carbon::today()->subDays(self::MAX_AGE_DAYS))) {
            throw new InvalidArgumentException(
                'Regularisation is only allowed for the last ' . self::MAX_AGE_DAYS . ' days.'
            );
        }

        if (!$in && !$out) {
            throw new InvalidArgumentException('Enter a check-in time, a check-out time, or both.');
        }

        if ($in && $out && Carbon::parse($out)->lte(Carbon::parse($in))) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }

        $duplicate = AttendanceRegularization::where('user_id', $employee->id)
            ->whereDate('date', $date->toDateString())
            ->pending()
            ->exists();

        if ($duplicate) {
            throw new InvalidArgumentException('You already have a pending request for this date.');
        }

        return AttendanceRegularization::create([
            'user_id'             => $employee->id,
            'date'                => $date->toDateString(),
            'requested_check_in'  => $in ? Carbon::parse($in)->format('H:i:s') : null,
            'requested_check_out' => $out ? Carbon::parse($out)->format('H:i:s') : null,
            'reason'              => $data['reason'],
            'status'              => 'pending',
        ]);
    }

    /**
     * Apply the requested times to the attendance row and re-run shift validation.
     *
     * @throws InvalidArgumentException
     */
    public function approve(AttendanceRegularization $request, User $reviewer, ?string $notes = null): Attendance
    {
        $this->guardReviewable($request, $reviewer);

        return DB::transaction(function () use ($request, $reviewer, $notes) {
            $attendance = Attendance::where('user_id', $request->user_id)
                ->whereDate('date', $request->date->toDateString())
                ->first();

            $attendance ??= new Attendance([
                'user_id' => $request->user_id,
                'date'    => $request->date->toDateString(),
            ]);

            $attendance->check_in  = $request->requested_check_in ?? $attendance->check_in;
            $attendance->check_out = $request->requested_check_out ?? $attendance->check_out;
            $attendance->status    = 'present';
            $attendance->save();

            // Recompute worked/overtime minutes and exceptions against the corrected times.
            $this->validationSvc->validate($attendance->fresh());

            // Free-clock days have no shift to validate against, so total the hours here.
            $attendance->refresh();
            if ($attendance->worked_minutes === null && $attendance->check_in && $attendance->check_out) {
                $attendance->update([
                    'worked_minutes' => (int) Carbon::parse($attendance->check_in)
                        ->diffInMinutes(Carbon::parse($attendance->check_out)),
                ]);
            }

            $request->update([
                'status'       => 'approved',
                'reviewed_by'  => $reviewer->id,
                'reviewed_at'  => now(),
                'review_notes' => $notes,
            ]);

            return $attendance->fresh();
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function reject(AttendanceRegularization $request, User $reviewer, string $notes): void
    {
        $this->guardReviewable($request, $reviewer);

        $request->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewer->id,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    private function guardReviewable(AttendanceRegularization $request, User $reviewer): void
    {
        if (!$request->isPending()) {
            throw new InvalidArgumentException('This request has already been reviewed.');
        }

        if ($request->user_id === $reviewer->id) {
            throw new InvalidArgumentException('You cannot review your own request.');
        }
    }
}
