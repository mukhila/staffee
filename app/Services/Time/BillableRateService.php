<?php

namespace App\Services\Time;

use App\Models\BillableRate;
use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;

class BillableRateService
{
    /**
     * Get the effective billable rate for a user/project at the given moment.
     * Returns null if no rate is configured.
     */
    public function getEffectiveRate(User $user, ?Project $project, ?Carbon $date = null): ?BillableRate
    {
        return BillableRate::resolve($user, $project, $date ?? now());
    }

    /**
     * Snapshot the current effective rate onto a completed time entry.
     * Called once when the timer stops.
     */
    public function snapshotRate(TimeTracker $entry): void
    {
        $project = $entry->project;
        $rate    = $this->getEffectiveRate($entry->user, $project, $entry->start_time->toCarbon());

        if ($rate) {
            $entry->rate_snapshot = $rate->hourly_rate;
            $entry->saveQuietly();
        }
    }

    /**
     * Revenue = hours × rate_snapshot (BCMath precision).
     */
    public function calculateRevenue(TimeTracker $entry): float
    {
        return $entry->revenue; // delegates to model attribute which uses bcmul
    }

    /**
     * Aggregate revenue for a collection of completed billable entries (BCMath).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<TimeTracker>  $entries
     */
    public function aggregateRevenue(\Illuminate\Database\Eloquent\Collection $entries): float
    {
        $total = '0';
        foreach ($entries->where('is_billable', true) as $e) {
            if ($e->hours_decimal !== null && $e->rate_snapshot !== null) {
                $total = bcadd($total, bcmul((string) $e->hours_decimal, (string) $e->rate_snapshot, 6), 6);
            }
        }
        return (float) bcadd($total, '0', 2); // round to cents
    }

    /**
     * Projected revenue for a given number of hours at the current effective rate.
     */
    public function projectRevenue(User $user, ?Project $project, float $estimatedHours): float
    {
        $rate = $this->getEffectiveRate($user, $project);
        if (!$rate) {
            return 0.0;
        }
        return (float) bcmul((string) $estimatedHours, (string) $rate->hourly_rate, 2);
    }

    /**
     * Create a new rate for the given user/project combination, automatically
     * closing any currently-open rate of the same specificity on effectiveFrom - 1 day.
     *
     * @param  User|null     $user          null → global or project-only rate
     * @param  Project|null  $project       null → global or user-only rate
     * @param  float         $newRate       Hourly rate in dollars
     * @param  Carbon        $effectiveFrom Date from which this rate applies
     * @param  string        $currency
     * @param  string|null   $notes
     */
    public function updateRate(
        ?User $user,
        ?Project $project,
        float $newRate,
        Carbon $effectiveFrom,
        string $currency = 'USD',
        ?string $notes = null
    ): BillableRate {
        $rateType = match (true) {
            $user !== null && $project !== null => 'user_project',
            $project !== null                   => 'project',
            $user !== null                      => 'user',
            default                             => 'global',
        };

        // Close any currently-open rate of the same type/scope
        BillableRate::where('rate_type', $rateType)
            ->where('user_id',    $user?->id)
            ->where('project_id', $project?->id)
            ->whereNull('effective_to')
            ->update([
                'effective_to' => $effectiveFrom->copy()->subDay()->toDateString(),
            ]);

        return BillableRate::create([
            'rate_type'      => $rateType,
            'user_id'        => $user?->id,
            'project_id'     => $project?->id,
            'hourly_rate'    => $newRate,
            'currency'       => strtoupper($currency),
            'effective_from' => $effectiveFrom->toDateString(),
            'created_by'     => auth()->id(),
            'notes'          => $notes,
        ]);
    }
}
