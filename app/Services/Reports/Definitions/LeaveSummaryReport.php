<?php

namespace App\Services\Reports\Definitions;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Reports\Concerns\SelectsStaff;
use App\Services\Reports\ReportDefinition;
use Carbon\Carbon;

class LeaveSummaryReport implements ReportDefinition
{
    use SelectsStaff;

    public function key(): string { return 'leave-summary'; }

    public function label(): string { return 'Leave summary'; }

    public function description(): string
    {
        return 'Leave starting in the period, by person and leave type: approved and still-pending days.';
    }

    public function canView(User $viewer): bool { return $viewer->hasPermission('view-reports'); }

    public function supportsDepartment(): bool { return true; }

    public function headers(User $viewer): array
    {
        return ['Employee', 'Department', 'Leave type', 'Approved days', 'Pending days', 'Rejected requests'];
    }

    public function rows(Carbon $from, Carbon $to, ?int $departmentId, User $viewer): array
    {
        $staff = $this->staff($departmentId)->keyBy('id');

        $requests = LeaveRequest::with('leaveType:id,name')
            ->whereIn('user_id', $staff->keys())
            ->whereDate('from_date', '>=', $from->toDateString())
            ->whereDate('from_date', '<=', $to->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get(['user_id', 'leave_type_id', 'type', 'days', 'status']);

        return $requests
            ->groupBy(fn ($r) => $r->user_id . '|' . ($r->leaveType?->name ?? ucfirst((string) $r->type)))
            ->map(function ($group, $key) use ($staff) {
                $user = $staff[(int) explode('|', $key, 2)[0]];

                return [
                    $user->name,
                    $user->department?->name ?? '',
                    explode('|', $key, 2)[1],
                    (float) $group->whereIn('status', ['approved', 'auto_approved'])->sum('days'),
                    (float) $group->whereIn('status', ['pending', 'manager_approved'])->sum('days'),
                    $group->where('status', 'rejected')->count(),
                ];
            })
            ->sortBy(fn ($r) => $r[0] . '|' . $r[2])
            ->values()
            ->all();
    }

    public function summary(array $rows): array
    {
        if (!$rows) {
            return ['No leave starts in this period.'];
        }

        $approved = array_sum(array_column($rows, 3));
        $pending  = array_sum(array_column($rows, 4));
        $people   = count(array_unique(array_column($rows, 0)));

        $lines = ["{$approved} approved leave days across {$people} " . ($people === 1 ? 'person' : 'people') . '.'];
        if ($pending > 0) {
            $lines[] = "{$pending} days are still waiting for approval.";
        }

        return $lines;
    }
}
