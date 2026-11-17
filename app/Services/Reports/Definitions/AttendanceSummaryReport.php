<?php

namespace App\Services\Reports\Definitions;

use App\Models\Attendance;
use App\Models\Shift\AttendanceException;
use App\Models\User;
use App\Services\Reports\Concerns\SelectsStaff;
use App\Services\Reports\ReportDefinition;
use Carbon\Carbon;

class AttendanceSummaryReport implements ReportDefinition
{
    use SelectsStaff;

    public function key(): string { return 'attendance-summary'; }

    public function label(): string { return 'Attendance summary'; }

    public function description(): string
    {
        return 'Per person: days present, absent and on leave, late arrivals, hours worked and overtime.';
    }

    public function canView(User $viewer): bool { return $viewer->hasPermission('view-reports'); }

    public function supportsDepartment(): bool { return true; }

    public function headers(User $viewer): array
    {
        return ['Employee', 'Department', 'Working days', 'Present', 'Half days', 'Absent', 'On leave', 'Late arrivals', 'Hours worked', 'Overtime hours', 'Attendance %'];
    }

    public function rows(Carbon $from, Carbon $to, ?int $departmentId, User $viewer): array
    {
        $staff       = $this->staff($departmentId);
        $workingDays = $this->workingDays($from, $to);

        $records = Attendance::whereIn('user_id', $staff->pluck('id'))
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['user_id', 'status', 'worked_minutes', 'overtime_minutes'])
            ->groupBy('user_id');

        $late = AttendanceException::whereIn('user_id', $staff->pluck('id'))
            ->where('exception_type', 'late_arrival')
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->selectRaw('user_id, COUNT(*) as n')
            ->groupBy('user_id')
            ->pluck('n', 'user_id');

        return $staff->map(function (User $user) use ($records, $late, $workingDays) {
            $mine     = $records->get($user->id, collect());
            $present  = $mine->where('status', 'present')->count();
            $half     = $mine->where('status', 'halfday')->count();
            $attended = $present + $half * 0.5;

            return [
                $user->name,
                $user->department?->name ?? '',
                $workingDays,
                $present,
                $half,
                $mine->where('status', 'absent')->count(),
                $mine->where('status', 'leave')->count(),
                (int) ($late[$user->id] ?? 0),
                self::hours($mine->sum('worked_minutes')),
                self::hours($mine->sum('overtime_minutes')),
                $workingDays > 0 ? round($attended / $workingDays * 100) : null,
            ];
        })->all();
    }

    public function summary(array $rows): array
    {
        if (!$rows) {
            return ['No staff matched this report.'];
        }

        $rates = array_filter(array_column($rows, 10), fn ($v) => $v !== null);
        $lines = [count($rows) . ' people, ' . ($rows[0][2] ?? 0) . ' working days.'];

        if ($rates) {
            $lines[] = 'Average attendance: ' . round(array_sum($rates) / count($rates)) . '%.';
        }

        $lates = collect($rows)->filter(fn ($r) => $r[7] > 0)->sortByDesc(7)->take(3);
        if ($lates->isNotEmpty()) {
            $lines[] = 'Most late arrivals: ' . $lates->map(fn ($r) => "{$r[0]} ({$r[7]})")->implode(', ') . '.';
        }

        return $lines;
    }
}
