<?php

namespace App\Services\Leave;

use App\Models\Attendance;
use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Shift\ShiftHoliday;
use App\Models\User;
use App\Notifications\Leave\LeaveApprovedNotification;
use App\Notifications\Leave\LeaveRejectedNotification;
use App\Notifications\Leave\LeaveSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function __construct(private readonly LeaveBalanceService $balanceSvc) {}

    /**
     * Submit a new leave request.
     *
     * @throws \InvalidArgumentException
     */
    public function submit(User $employee, array $data): LeaveRequest
    {
        /** @var LeaveType $type */
        $type   = LeaveType::findOrFail($data['leave_type_id']);
        $from   = Carbon::parse($data['from_date']);
        $to     = Carbon::parse($data['to_date']);
        $days   = $this->calculateWorkingDays($from, $to, (bool) ($data['half_day'] ?? false));
        $year   = $from->year;
        $policy = $type->getPolicyFor($employee);

        // ── Validation ────────────────────────────────────────────────────────

        if ($this->balanceSvc->hasOverlap($employee, $from, $to)) {
            throw new \InvalidArgumentException('You already have a leave request for overlapping dates.');
        }

        if ($policy && $policy->min_notice_days > 0 && $from->diffInDays(now()) < $policy->min_notice_days) {
            throw new \InvalidArgumentException(
                "This leave type requires {$policy->min_notice_days} days advance notice."
            );
        }

        if ($policy && $policy->max_consecutive_days && $days > $policy->max_consecutive_days) {
            throw new \InvalidArgumentException(
                "Maximum consecutive days allowed: {$policy->max_consecutive_days}."
            );
        }

        if ($type->requires_approval && !$this->balanceSvc->hasSufficientBalance($employee, $type, $days, $year)) {
            throw new \InvalidArgumentException("Insufficient {$type->name} balance.");
        }

        // ── Determine initial status ──────────────────────────────────────────

        $autoApprove = $policy && $policy->isAutoApprove((int) $days);
        $status      = $autoApprove ? 'auto_approved' : 'pending';

        // ── Create request ────────────────────────────────────────────────────

        return DB::transaction(function () use (
            $employee, $data, $type, $from, $to, $days, $status, $autoApprove, $policy
        ) {
            $request = LeaveRequest::create([
                'user_id'         => $employee->id,
                'leave_type_id'   => $type->id,
                'type'            => $type->code, // legacy column
                'from_date'       => $from->toDateString(),
                'to_date'         => $to->toDateString(),
                'days'            => $days,
                'half_day'        => $data['half_day'] ?? false,
                'half_day_period' => $data['half_day_period'] ?? null,
                'reason'          => $data['reason'],
                'status'          => $status,
                'auto_approved'   => $autoApprove,
            ]);

            if ($autoApprove) {
                $this->balanceSvc->consumeLeave($request);
                $this->stampAttendance($request);
            } else {
                $this->balanceSvc->reservePending($request);
            }

            // Notify
            if ($autoApprove) {
                $employee->notify(new LeaveApprovedNotification($request, null));
            } else {
                $manager = $employee->manager;
                if ($manager) {
                    $manager->notify(new LeaveSubmittedNotification($request, $employee));
                }
                // Also notify admins
                User::where('role', 'admin')->each(
                    fn ($admin) => $admin->notify(new LeaveSubmittedNotification($request, $employee))
                );
            }

            return $request;
        });
    }

    /**
     * Manager-level approval (level 1).
     */
    public function managerApprove(LeaveRequest $request, User $manager, ?string $notes = null): void
    {
        abort_if(!in_array($request->status, ['pending']), 422, 'Cannot approve in current state.');

        DB::transaction(function () use ($request, $manager, $notes) {
            $policy = $request->leaveType?->getPolicyFor($request->user);

            // If HR approval is also required, move to manager_approved; else fully approve
            $nextStatus = ($policy && $policy->requires_hr_approval)
                ? 'manager_approved'
                : 'approved';

            $request->update([
                'status'              => $nextStatus,
                'manager_approved_by' => $manager->id,
                'manager_approved_at' => now(),
                'reviewed_by'         => $manager->id,
            ]);

            LeaveApproval::create([
                'leave_request_id' => $request->id,
                'approver_id'      => $manager->id,
                'level'            => 1,
                'action'           => 'approved',
                'notes'            => $notes,
                'acted_at'         => now(),
            ]);

            if ($nextStatus === 'approved') {
                $this->balanceSvc->consumeLeave($request);
                $this->stampAttendance($request);
                $request->user->notify(new LeaveApprovedNotification($request, $manager));
            }
        });
    }

    /**
     * HR-level approval (level 2) — final approval.
     */
    public function hrApprove(LeaveRequest $request, User $hr, ?string $notes = null): void
    {
        abort_if($request->status !== 'manager_approved', 422, 'Requires manager approval first.');

        DB::transaction(function () use ($request, $hr, $notes) {
            $request->update([
                'status'          => 'approved',
                'hr_approved_by'  => $hr->id,
                'hr_approved_at'  => now(),
                'reviewed_by'     => $hr->id,
            ]);

            LeaveApproval::create([
                'leave_request_id' => $request->id,
                'approver_id'      => $hr->id,
                'level'            => 2,
                'action'           => 'approved',
                'notes'            => $notes,
                'acted_at'         => now(),
            ]);

            $this->balanceSvc->consumeLeave($request);
            $this->stampAttendance($request);
            $request->user->notify(new LeaveApprovedNotification($request, $hr));
        });
    }

    /**
     * Reject at any level.
     */
    public function reject(LeaveRequest $request, User $reviewer, string $reason, int $level = 1): void
    {
        abort_if(in_array($request->status, ['approved', 'auto_approved', 'rejected', 'cancelled']), 422, 'Cannot reject.');

        DB::transaction(function () use ($request, $reviewer, $reason, $level) {
            $request->update([
                'status'           => 'rejected',
                'reviewed_by'      => $reviewer->id,
                'rejection_reason' => $reason,
            ]);

            LeaveApproval::create([
                'leave_request_id' => $request->id,
                'approver_id'      => $reviewer->id,
                'level'            => $level,
                'action'           => 'rejected',
                'notes'            => $reason,
                'acted_at'         => now(),
            ]);

            $this->balanceSvc->releasePending($request);
            $request->user->notify(new LeaveRejectedNotification($request, $reviewer, $reason));
        });
    }

    /**
     * Employee cancels their own request.
     */
    public function cancel(LeaveRequest $request, string $reason = ''): void
    {
        abort_unless($request->isCancellable(), 422, 'This request cannot be cancelled.');

        DB::transaction(function () use ($request, $reason) {
            $wasApproved = $request->isApproved();

            $request->update([
                'status'           => 'cancelled',
                'cancelled_at'     => now(),
                'cancelled_reason' => $reason ?: null,
            ]);

            if ($wasApproved) {
                $this->balanceSvc->reverseConsumption($request);
                $this->removeAttendanceStamps($request);
            } else {
                $this->balanceSvc->releasePending($request);
            }
        });
    }

    // ── Attendance integration ────────────────────────────────────────────────

    /**
     * Create attendance rows (status=leave) for each working day in the approved range.
     */
    public function stampAttendance(LeaveRequest $request): void
    {
        $date = $request->from_date->copy();

        while ($date->lte($request->to_date)) {
            if (!in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                Attendance::updateOrCreate(
                    ['user_id' => $request->user_id, 'date' => $date->toDateString()],
                    ['status' => 'leave', 'check_in' => null, 'check_out' => null]
                );
            }
            $date->addDay();
        }
    }

    /**
     * Remove attendance rows stamped for a cancelled leave (only if untouched).
     */
    private function removeAttendanceStamps(LeaveRequest $request): void
    {
        Attendance::where('user_id', $request->user_id)
            ->where('status', 'leave')
            ->whereNull('check_in')
            ->whereBetween('date', [$request->from_date->toDateString(), $request->to_date->toDateString()])
            ->delete();
    }

    // ── Working day calculation ───────────────────────────────────────────────

    /**
     * Count working days (Mon–Fri, excluding public holidays) between from and to inclusive.
     * Half-day on a single day returns 0.5.
     */
    private function calculateWorkingDays(Carbon $from, Carbon $to, bool $halfDay): float
    {
        if ($halfDay && $from->eq($to)) {
            return 0.5;
        }

        // Fetch active public holidays in the requested range (reuse ShiftHoliday)
        $holidays = ShiftHoliday::active()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->flip() // use as O(1) lookup map
            ->all();

        $days = 0;
        $date = $from->copy();

        while ($date->lte($to)) {
            if (!in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                && !isset($holidays[$date->toDateString()])) {
                $days++;
            }
            $date->addDay();
        }

        return (float) $days;
    }
}
