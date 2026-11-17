<?php

namespace App\Services\Dashboard;

use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use App\Models\Expense\ExpenseClaim;
use App\Models\HR\DocumentRequest;
use App\Models\Leave\CompOffCredit;
use App\Models\LeaveRequest;
use App\Models\Payroll\PayrollAdjustment;
use App\Models\Shift\AttendanceException;
use App\Models\TimeTracker;
use App\Models\User;
use App\Services\Leave\LeaveApprovalService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Role-aware dashboard cards: what is waiting on me, how my team is doing
 * today, and (for admins) the whole company today.
 */
class DashboardInsightsService
{
    public function __construct(private readonly LeaveApprovalService $leaveApprovals) {}

    /**
     * Items waiting for this person's decision, only for things they are allowed to act on.
     *
     * @return Collection<int, array{label: string, count: int, url: string, icon: string}>
     */
    public function pendingActions(User $user): Collection
    {
        $items = collect();
        $add = function (string $label, int $count, string $url, string $icon) use ($items) {
            if ($count > 0) {
                $items->push(compact('label', 'count', 'url', 'icon'));
            }
        };

        if ($user->hasPermission('approve-leave')) {
            $add('Leave requests', $this->leaveApprovals->getPendingForApprover($user)->count(), route('admin.leaves.approvals'), 'fi-rr-calendar-exclamation');
            $add('Comp-off claims', CompOffCredit::pending()->where('user_id', '!=', $user->id)->count(), route('admin.leaves.comp-off.index'), 'fi-rr-calendar-plus');
        }

        if ($user->hasPermission('manage-attendance')) {
            $add('Attendance corrections', AttendanceRegularization::pending()->where('user_id', '!=', $user->id)->count(), route('admin.attendances.regularizations.index'), 'fi-rr-time-check');
        }

        if ($user->isAdmin()) {
            $add('Time entries to approve', TimeTracker::where('approval_status', 'pending')->count(), route('admin.time-entries.approvals.index'), 'fi-rr-clock');
            $add('Attendance exceptions', AttendanceException::where('status', 'pending')->whereDate('date', '>=', now()->subDays(7)->toDateString())->count(), route('admin.shifts.exceptions.index'), 'fi-rr-triangle-warning');
            $add('Expense claims', ExpenseClaim::where('status', 'submitted')->count(), route('admin.expenses.index', ['status' => 'submitted']), 'fi-rr-receipt');
            $add('Document requests', DocumentRequest::where('status', 'pending')->count(), route('admin.document-requests.index'), 'fi-rr-document');
            $add('Payroll adjustments', PayrollAdjustment::where('status', 'pending')->count(), route('admin.payroll.adjustments.index'), 'fi-rr-money-check-edit');
        }

        return $items;
    }

    /**
     * Where each of the person's direct reports is today.
     *
     * @return Collection<int, array{user: User, state: string, label: string, time: ?string}>
     */
    public function teamToday(User $manager): Collection
    {
        $reports = User::where('reporting_to', $manager->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'avatar']);

        return $this->statusFor($reports);
    }

    /** Company-wide picture for admins. */
    public function companyToday(): array
    {
        $staff = User::where('is_active', true)->where('role', '!=', 'admin')->get(['id', 'name']);
        $statuses = $this->statusFor($staff)->countBy('state');

        return [
            'total'    => $staff->count(),
            'in'       => ($statuses['in'] ?? 0) + ($statuses['done'] ?? 0),
            'on_leave' => $statuses['leave'] ?? 0,
            'not_in'   => $statuses['not_in'] ?? 0,
        ];
    }

    /**
     * @param  Collection<int, User>  $people
     * @return Collection<int, array{user: User, state: string, label: string, time: ?string}>
     */
    private function statusFor(Collection $people): Collection
    {
        if ($people->isEmpty()) {
            return collect();
        }

        $today = Carbon::today()->toDateString();
        $ids   = $people->pluck('id');

        $attendance = Attendance::whereIn('user_id', $ids)->whereDate('date', $today)->get()->keyBy('user_id');

        $onLeave = LeaveRequest::whereIn('user_id', $ids)
            ->whereIn('status', ['approved', 'auto_approved'])
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->pluck('user_id')
            ->flip();

        $order = ['in' => 0, 'done' => 1, 'not_in' => 2, 'leave' => 3];

        return $people->map(function (User $user) use ($attendance, $onLeave) {
            $a = $attendance->get($user->id);

            if ($a && $a->check_in && $a->check_out) {
                return ['user' => $user, 'state' => 'done', 'label' => 'Finished', 'time' => substr($a->check_in, 0, 5) . '–' . substr($a->check_out, 0, 5)];
            }
            if ($a && $a->check_in) {
                return ['user' => $user, 'state' => 'in', 'label' => 'In', 'time' => 'since ' . substr($a->check_in, 0, 5)];
            }
            if ($onLeave->has($user->id) || ($a && $a->status === 'leave')) {
                return ['user' => $user, 'state' => 'leave', 'label' => 'On leave', 'time' => null];
            }

            return ['user' => $user, 'state' => 'not_in', 'label' => 'Not in yet', 'time' => null];
        })->sortBy(fn ($row) => [$order[$row['state']], $row['user']->name])->values();
    }
}
