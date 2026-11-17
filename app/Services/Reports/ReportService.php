<?php

namespace App\Services\Reports;

use App\Models\Department;
use App\Models\User;
use App\Services\Reports\Definitions\AttendanceSummaryReport;
use App\Services\Reports\Definitions\HeadcountReport;
use App\Services\Reports\Definitions\LeaveSummaryReport;
use App\Services\Reports\Definitions\ProjectStatusReport;
use Carbon\Carbon;
use InvalidArgumentException;

class ReportService
{
    private const DEFINITIONS = [
        AttendanceSummaryReport::class,
        LeaveSummaryReport::class,
        HeadcountReport::class,
        ProjectStatusReport::class,
    ];

    public const PERIODS = [
        'last-week'  => 'Last week',
        'last-month' => 'Last month',
        'this-week'  => 'This week so far',
        'this-month' => 'This month so far',
    ];

    /** @return array<string, ReportDefinition> */
    public function all(): array
    {
        return collect(self::DEFINITIONS)
            ->map(fn ($class) => app($class))
            ->keyBy(fn (ReportDefinition $r) => $r->key())
            ->all();
    }

    /** @return array<string, ReportDefinition> */
    public function availableTo(User $user): array
    {
        return array_filter($this->all(), fn (ReportDefinition $r) => $r->canView($user));
    }

    public function find(string $key): ReportDefinition
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Unknown report \"{$key}\".");
    }

    /**
     * Date range for a named period, relative to a day.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function period(string $period, ?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        [$from, $to] = match ($period) {
            'last-week'  => [$today->copy()->subWeek()->startOfWeek(), $today->copy()->subWeek()->endOfWeek()],
            'last-month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'this-week'  => [$today->copy()->startOfWeek(), $today->copy()->endOfDay()],
            'this-month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            default      => throw new InvalidArgumentException("Unknown period \"{$period}\"."),
        };

        return [$from->startOfDay(), $to->endOfDay(), $from->format('d M Y') . ' – ' . $to->format('d M Y')];
    }

    /**
     * Build the report for one person.
     *
     * @return array{filename: string, csv: string, rows: int, summary: string[], range: string, title: string}
     *
     * @throws InvalidArgumentException when the person may not see this report
     */
    public function build(ReportDefinition $report, User $viewer, Carbon $from, Carbon $to, string $range, ?int $departmentId = null): array
    {
        if (!$report->canView($viewer)) {
            throw new InvalidArgumentException('You do not have access to this report.');
        }

        $departmentId = $report->supportsDepartment() ? $departmentId : null;
        $rows         = $report->rows($from, $to, $departmentId, $viewer);
        $department   = $departmentId ? Department::find($departmentId)?->name : null;

        $title = $report->label() . ($department ? " – {$department}" : '');

        return [
            'filename' => sprintf('staffee-%s%s-%s-to-%s.csv',
                $report->key(),
                $department ? '-' . \Illuminate\Support\Str::slug($department) : '',
                $from->toDateString(),
                $to->toDateString()
            ),
            'csv'      => $this->csv($report->headers($viewer), $rows),
            'rows'     => count($rows),
            'summary'  => $report->summary($rows),
            'range'    => $range,
            'title'    => $title,
        ];
    }

    /** UTF-8 CSV with a byte-order mark so Excel opens names correctly. */
    private function csv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, ',', '"', '');

        foreach ($rows as $row) {
            // Stop spreadsheet formulas sneaking in through names or text fields.
            $row = array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $row);
            fputcsv($handle, $row, ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
