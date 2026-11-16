<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeaveApprovalDelegation;
use App\Models\Leave\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveApprovalService
{
    public function __construct(private readonly LeaveRequestService $requestSvc) {}

    /**
     * Which approval levels are required for this request (based on its policy).
     * Returns [level => role_label, ...].
     */
    public function getRequiredApprovers(LeaveRequest $request): array
    {
        $policy = $request->leaveType?->getPolicyFor($request->user);

        if (!$policy) {
            return [1 => 'Manager'];
        }

        $levels = [];
        if ($policy->requires_manager_approval) {
            $levels[1] = 'Manager';
        }
        if ($policy->requires_hr_approval) {
            $levels[2] = 'HR';
        }

        return $levels ?: [1 => 'Manager'];
    }

    /**
     * Submit an approval or rejection at a given level.
     */
    public function submitApproval(
        LeaveRequest $request,
        User $approver,
        bool $approved,
        ?string $notes = null,
        int $level = 1,
    ): void {
        if ($approved) {
            match ($level) {
                1 => $this->requestSvc->managerApprove($request, $approver, $notes),
                2 => $this->requestSvc->hrApprove($request, $approver, $notes),
                default => throw new \InvalidArgumentException("Unsupported approval level: {$level}"),
            };
        } else {
            $this->requestSvc->reject($request, $approver, $notes ?? 'Request rejected.', $level);
        }
    }

    /**
     * Structured approval status: one entry per required level.
     */
    public function getApprovalStatus(LeaveRequest $request): Collection
    {
        $request->loadMissing('approvals.approver');
        $required = $this->getRequiredApprovers($request);

        return collect($required)->map(function (string $roleLabel, int $level) use ($request) {
            $approval = $request->approvals->firstWhere('level', $level);

            return [
                'level'    => $level,
                'role'     => $roleLabel,
                'status'   => $approval?->action ?? 'pending',
                'approver' => $approval?->approver?->name,
                'notes'    => $approval?->notes,
                'acted_at' => $approval?->acted_at,
                'color'    => $approval
                    ? (LeaveApproval::ACTION_COLORS[$approval->action] ?? 'secondary')
                    : ($request->status === 'pending' && $level === 1 ? 'warning' : 'light'),
            ];
        })->values();
    }

    /**
     * Leave requests waiting for a specific approver's action.
     */
    public function getPendingForApprover(User $approver): \Illuminate\Database\Eloquent\Collection
    {
        $query = LeaveRequest::with(['user.department', 'leaveType'])
            ->orderByDesc('created_at');

        if ($approver->role === 'admin') {
            // Admin/HR can act on any pending or manager_approved request
            return $query->whereIn('status', ['pending', 'manager_approved'])->get();
        }

        // Manager sees their own department's pending requests (not their own),
        // plus the departments of anyone who has delegated approvals to them.
        $departmentIds = $this->approvableDepartmentIds($approver);

        return $query
            ->where('status', 'pending')
            ->where('user_id', '!=', $approver->id)
            ->whereHas('user', fn ($q) => $q->whereIn('department_id', $departmentIds))
            ->get();
    }

    /**
     * Departments this approver may act for today: their own, and those of
     * managers who currently delegate their leave approvals to them.
     *
     * @return int[]
     */
    public function approvableDepartmentIds(User $approver): array
    {
        $delegated = LeaveApprovalDelegation::activeOn()
            ->where('delegate_id', $approver->id)
            ->with('delegator:id,department_id')
            ->get()
            ->pluck('delegator.department_id');

        return $delegated
            ->push($approver->department_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** The user currently covering this manager's leave approvals, if any. */
    public function activeDelegateFor(User $manager): ?User
    {
        return LeaveApprovalDelegation::activeOn()
            ->where('delegator_id', $manager->id)
            ->with('delegate')
            ->latest('id')
            ->first()
            ?->delegate;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function delegate(User $delegator, User $delegate, Carbon $from, Carbon $to, ?string $reason = null): LeaveApprovalDelegation
    {
        if ($delegator->id === $delegate->id) {
            throw new \InvalidArgumentException('You cannot delegate approvals to yourself.');
        }

        if ($to->lt($from)) {
            throw new \InvalidArgumentException('The end date must be on or after the start date.');
        }

        if (!$delegate->hasPermission('approve-leave')) {
            throw new \InvalidArgumentException("{$delegate->name} does not have permission to approve leave.");
        }

        $overlap = LeaveApprovalDelegation::where('delegator_id', $delegator->id)
            ->whereDate('from_date', '<=', $to->toDateString())
            ->whereDate('to_date', '>=', $from->toDateString())
            ->exists();

        if ($overlap) {
            throw new \InvalidArgumentException('You already have a delegation covering those dates.');
        }

        return LeaveApprovalDelegation::create([
            'delegator_id' => $delegator->id,
            'delegate_id'  => $delegate->id,
            'from_date'    => $from->toDateString(),
            'to_date'      => $to->toDateString(),
            'reason'       => $reason,
        ]);
    }

    /**
     * Can this approver act on the request at the current step?
     */
    public function canAct(LeaveRequest $request, User $approver): bool
    {
        if ($approver->role === 'admin') {
            return in_array($request->status, ['pending', 'manager_approved']);
        }

        return $request->status === 'pending'
            && in_array($request->user->department_id, $this->approvableDepartmentIds($approver), true)
            && $approver->id !== $request->user_id;
    }
}
