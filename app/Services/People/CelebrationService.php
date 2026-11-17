<?php

namespace App\Services\People;

use App\Models\HR\EmployeeProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Upcoming birthdays and work anniversaries for active staff.
 *
 * Only the day and month of a birthday are ever exposed, never the year or age.
 */
class CelebrationService
{
    /**
     * @return Collection<int, array{user: \App\Models\User, type: string, date: Carbon, years: ?int, is_today: bool}>
     */
    public function upcoming(Carbon $from, int $days): Collection
    {
        $from = $from->copy()->startOfDay();
        $to   = $from->copy()->addDays($days - 1)->endOfDay();

        $profiles = EmployeeProfile::with('user:id,name,designation,department_id,avatar,is_active,employment_status')
            ->where(fn ($q) => $q->whereNotNull('date_of_birth')->orWhereNotNull('joining_date'))
            ->get(['id', 'user_id', 'date_of_birth', 'joining_date'])
            ->filter(fn ($p) => $p->user && $p->user->is_active
                && !in_array($p->user->employment_status, ['terminated', 'resigned'], true));

        $events = collect();

        foreach ($profiles as $profile) {
            if ($profile->date_of_birth && ($date = $this->nextOccurrence(Carbon::parse($profile->date_of_birth), $from, $to))) {
                $events->push(['user' => $profile->user, 'type' => 'birthday', 'date' => $date, 'years' => null]);
            }

            if ($profile->joining_date && ($date = $this->nextOccurrence(Carbon::parse($profile->joining_date), $from, $to))) {
                $years = $date->year - Carbon::parse($profile->joining_date)->year;
                if ($years >= 1) {
                    $events->push(['user' => $profile->user, 'type' => 'anniversary', 'date' => $date, 'years' => $years]);
                }
            }
        }

        $today = Carbon::today();

        return $events
            ->map(fn ($e) => $e + ['is_today' => $e['date']->isSameDay($today)])
            ->sortBy([fn ($a, $b) => $a['date'] <=> $b['date'], fn ($a, $b) => strcmp($a['user']->name, $b['user']->name)])
            ->values();
    }

    /**
     * The date this anniversary falls on within [from, to], if it does.
     * 29 February is celebrated on 28 February in other years.
     */
    private function nextOccurrence(Carbon $original, Carbon $from, Carbon $to): ?Carbon
    {
        foreach (array_unique([$from->year, $to->year]) as $year) {
            $day = min($original->day, Carbon::create($year, $original->month, 1)->daysInMonth);
            $date = Carbon::create($year, $original->month, $day)->startOfDay();

            if ($date->betweenIncluded($from, $to)) {
                return $date;
            }
        }

        return null;
    }
}
