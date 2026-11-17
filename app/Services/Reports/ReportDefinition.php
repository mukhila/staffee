<?php

namespace App\Services\Reports;

use App\Models\User;
use Carbon\Carbon;

/**
 * A report that can be downloaded or emailed on a schedule as CSV.
 */
interface ReportDefinition
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /** Whether this person may receive the report (checked again at every send). */
    public function canView(User $viewer): bool;

    /** Whether a department filter applies to this report. */
    public function supportsDepartment(): bool;

    /** @return string[] */
    public function headers(User $viewer): array;

    /** @return array<int, array<int, string|int|float|null>> */
    public function rows(Carbon $from, Carbon $to, ?int $departmentId, User $viewer): array;

    /**
     * A few plain-language lines for the email body.
     *
     * @return string[]
     */
    public function summary(array $rows): array;
}
