<?php

namespace App\Services\Monitoring;

use App\Models\Monitoring\MonitoringActivityLog;
use App\Models\Monitoring\MonitoringAppRule;
use App\Models\Monitoring\MonitoringIdlePeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Classifies tracked activity as productive, neutral or unproductive using the
 * admin-defined rules, and summarises it for reports and the weekly email.
 */
class ProductivityService
{
    public const DEFAULT_CATEGORY = 'neutral';

    private ?Collection $rules = null;

    /**
     * Category for one activity sample.
     *
     * Window-title rules (used for websites) win over application rules, and
     * within each type the longest matching pattern wins, so "YouTube Studio"
     * can be productive while "YouTube" is not.
     */
    public function classify(?string $appName, ?string $windowTitle): string
    {
        return $this->match('title', $windowTitle)
            ?? $this->match('app', $appName)
            ?? self::DEFAULT_CATEGORY;
    }

    /** True when at least one rule matches, i.e. the activity is not just defaulting to neutral. */
    public function isCategorised(?string $appName, ?string $windowTitle): bool
    {
        return $this->match('title', $windowTitle) !== null || $this->match('app', $appName) !== null;
    }

    /**
     * Activity summary for one or more users over a period.
     *
     * @param  int|int[]  $userIds
     * @return array{
     *   seconds: array{productive:int, neutral:int, unproductive:int},
     *   active_seconds: int, idle_seconds: int, productive_pct: int,
     *   top_apps: Collection<int, array{app:string, seconds:int, category:string}>
     * }
     */
    public function summarise(int|array $userIds, Carbon $from, Carbon $to, int $topApps = 5): array
    {
        $userIds = (array) $userIds;

        // Aggregate in SQL first; a week of one-minute samples collapses to a few hundred rows.
        $rows = MonitoringActivityLog::query()
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw('active_app_name, active_window_title, SUM(duration_seconds) as seconds')
            ->groupBy('active_app_name', 'active_window_title')
            ->get();

        $seconds = array_fill_keys(MonitoringAppRule::CATEGORIES, 0);
        $apps    = [];

        foreach ($rows as $row) {
            $category = $this->classify($row->active_app_name, $row->active_window_title);
            $duration = (int) $row->seconds;

            $seconds[$category] += $duration;

            $app = $row->active_app_name ?: 'Unknown';
            $apps[$app] ??= ['app' => $app, 'seconds' => 0, 'by_category' => array_fill_keys(MonitoringAppRule::CATEGORIES, 0)];
            $apps[$app]['seconds'] += $duration;
            $apps[$app]['by_category'][$category] += $duration;
        }

        $active = array_sum($seconds);

        $idle = (int) MonitoringIdlePeriod::whereIn('user_id', $userIds)
            ->whereBetween('idle_start', [$from, $to])
            ->sum('duration_seconds');

        $top = collect($apps)
            ->sortByDesc('seconds')
            ->take($topApps)
            ->map(fn (array $app) => [
                'app'      => $app['app'],
                'seconds'  => $app['seconds'],
                // An app can span categories (a browser); show the one most of its time fell in.
                'category' => array_search(max($app['by_category']), $app['by_category'], true),
            ])
            ->values();

        return [
            'seconds'        => $seconds,
            'active_seconds' => $active,
            'idle_seconds'   => $idle,
            'productive_pct' => $active > 0 ? (int) round($seconds['productive'] / $active * 100) : 0,
            'top_apps'       => $top,
        ];
    }

    /**
     * Most-used applications in the period that no rule covers yet, so an admin
     * can see what is worth categorising.
     *
     * @return Collection<int, array{app:string, seconds:int}>
     */
    public function uncategorisedApps(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return MonitoringActivityLog::query()
            ->where('is_active', true)
            ->whereBetween('recorded_at', [$from, $to])
            ->whereNotNull('active_app_name')
            ->selectRaw('active_app_name, SUM(duration_seconds) as seconds')
            ->groupBy('active_app_name')
            ->orderByDesc('seconds')
            ->limit(200)
            ->get()
            ->reject(fn ($row) => $this->match('app', $row->active_app_name) !== null)
            ->take($limit)
            ->map(fn ($row) => ['app' => $row->active_app_name, 'seconds' => (int) $row->seconds])
            ->values();
    }

    /** "3h 05m" / "42m" — for views and emails. */
    public static function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $hours   = intdiv($minutes, 60);

        return $hours > 0 ? sprintf('%dh %02dm', $hours, $minutes % 60) : "{$minutes}m";
    }

    /** Forget cached rules (after an admin edits them in the same request). */
    public function refreshRules(): void
    {
        $this->rules = null;
    }

    private function match(string $type, ?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $haystack = mb_strtolower($value);

        return $this->rules()
            ->where('match_type', $type)
            ->first(fn (MonitoringAppRule $rule) => str_contains($haystack, mb_strtolower($rule->pattern)))
            ?->category;
    }

    /** All rules, longest pattern first so the most specific rule is found first. */
    private function rules(): Collection
    {
        return $this->rules ??= MonitoringAppRule::all()
            ->sortByDesc(fn (MonitoringAppRule $rule) => mb_strlen($rule->pattern))
            ->values();
    }
}
