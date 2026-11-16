<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Monitoring\MonitoringSetting;
use App\Services\Monitoring\ProductivityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Lets each employee see the activity data recorded about them — and only them.
 */
class MyActivityController extends Controller
{
    public function __construct(private readonly ProductivityService $productivity) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $data      = $request->validate(['week' => 'nullable|date']);
        $weekStart = isset($data['week'])
            ? Carbon::parse($data['week'])->startOfWeek()
            : now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $summary = $this->productivity->summarise($user->id, $weekStart, $weekEnd, 8);

        $days = collect(range(0, 6))->map(function (int $offset) use ($user, $weekStart) {
            $day = $weekStart->copy()->addDays($offset);

            return [
                'date'    => $day,
                'summary' => $this->productivity->summarise($user->id, $day->copy()->startOfDay(), $day->copy()->endOfDay(), 0),
            ];
        });

        $monitoringEnabled = (bool) MonitoringSetting::forUser($user)->enabled;

        return view('staff.my-activity', compact('summary', 'days', 'weekStart', 'weekEnd', 'monitoringEnabled'));
    }
}
