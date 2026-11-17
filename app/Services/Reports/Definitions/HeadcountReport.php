<?php

namespace App\Services\Reports\Definitions;

use App\Models\Department;
use App\Models\HR\EmployeeProfile;
use App\Models\User;
use App\Services\Reports\ReportDefinition;
use Carbon\Carbon;

class HeadcountReport implements ReportDefinition
{
    public function key(): string { return 'headcount'; }

    public function label(): string { return 'Headcount by department'; }

    public function description(): string
    {
        return 'Active staff per department today, who joined in the period, and who is on probation or serving notice.';
    }

    public function canView(User $viewer): bool { return $viewer->hasPermission('view-staff'); }

    public function supportsDepartment(): bool { return false; }

    public function headers(User $viewer): array
    {
        return ['Department', 'Active staff', 'Joined in period', 'On probation', 'Serving notice'];
    }

    public function rows(Carbon $from, Carbon $to, ?int $departmentId, User $viewer): array
    {
        $staff = User::where('is_active', true)
            ->where('role', '!=', 'admin')
            ->get(['id', 'department_id', 'employment_status']);

        $joined = EmployeeProfile::whereIn('user_id', $staff->pluck('id'))
            ->whereDate('joining_date', '>=', $from->toDateString())
            ->whereDate('joining_date', '<=', $to->toDateString())
            ->pluck('user_id')
            ->flip();

        $names = Department::pluck('name', 'id');

        $rows = $staff->groupBy(fn ($u) => $u->department_id ?? 0)
            ->map(fn ($group, $deptId) => [
                $names[$deptId] ?? 'No department',
                $group->count(),
                $group->filter(fn ($u) => $joined->has($u->id))->count(),
                $group->where('employment_status', 'probation')->count(),
                $group->where('employment_status', 'notice_period')->count(),
            ])
            ->sortBy(0)
            ->values()
            ->all();

        if ($rows) {
            $rows[] = ['Total', ...array_map(fn ($i) => array_sum(array_column($rows, $i)), [1, 2, 3, 4])];
        }

        return $rows;
    }

    public function summary(array $rows): array
    {
        $total = end($rows);
        if (!$total) {
            return ['No active staff.'];
        }

        return ["{$total[1]} active staff; {$total[2]} joined in this period; {$total[3]} on probation; {$total[4]} serving notice."];
    }
}
