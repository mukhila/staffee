<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\User;
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

        // Manager sees their own department's pending requests (not their own)
        return $query
            ->where('status', 'pending')
            ->where('user_id', '!=', $approver->id)
            ->whereHas('user', fn ($q) => $q->where('department_id', $approver->department_id))
            ->get();
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
            && $approver->department_id === $request->user->department_id
            && $approver->id !== $request->user_id;
    }
}
