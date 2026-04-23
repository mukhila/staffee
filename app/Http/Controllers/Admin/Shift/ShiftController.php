<?php

namespace App\Http\Controllers\Admin\Shift;

use App\Http\Controllers\Controller;
use App\Models\Shift\Shift;
use App\Models\Shift\ShiftPattern;
use App\Models\Shift\ShiftPatternDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('assignments')
            ->with('createdBy')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('admin.shifts.create', ['shift' => new Shift(), 'editing' => false]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['created_by']    = auth()->id();
        $validated['working_days']  = $request->input('working_days', ['Mon','Tue','Wed','Thu','Fri']);
        $validated['crosses_midnight'] = $this->detectCrossesMidnight(
            $validated['start_time'],
            $validated['end_time'],
            $request->boolean('crosses_midnight')
        );

        $shift = DB::transaction(function () use ($validated, $request) {
            $shift = Shift::create($validated);
            $this->syncPattern($shift, $request);
            return $shift;
        });

        return redirect()->route('admin.shifts.show', $shift)->with('success', 'Shift created.');
    }

    public function show(Shift $shift)
    {
        $shift->load(['patterns.days', 'assignments' => fn ($q) => $q->active()->with('user')->latest('effective_from')->take(20)]);
        return view('admin.shifts.show', compact('shift'));
    }

    public function edit(Shift $shift)
    {
        $shift->load('patterns.days');
        return view('admin.shifts.create', ['shift' => $shift, 'editing' => true]);
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $this->validated($request);
        $validated['working_days']      = $request->input('working_days', ['Mon','Tue','Wed','Thu','Fri']);
        $validated['crosses_midnight']  = $this->detectCrossesMidnight(
            $validated['start_time'],
            $validated['end_time'],
            $request->boolean('crosses_midnight')
        );

        DB::transaction(function () use ($shift, $validated, $request) {
            $shift->update($validated);
            $this->syncPattern($shift, $request);
        });

        return redirect()->route('admin.shifts.show', $shift)->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        abort_if($shift->assignments()->active()->exists(), 422, 'Cannot delete a shift with active assignments.');
        $shift->delete();
        return redirect()->route('admin.shifts.index')->with('success', 'Shift deleted.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'                      => 'required|string|max:100',
            'code'                      => 'required|string|max:20',
            'shift_type'                => 'required|in:fixed,rotating,flexible,night,hybrid',
            'start_time'                => 'required|date_format:H:i',
            'end_time'                  => 'required|date_format:H:i',
            'break_duration_minutes'    => 'required|integer|min:0|max:240',
            'grace_in_minutes'          => 'required|integer|min:0|max:60',
            'grace_out_minutes'         => 'required|integer|min:0|max:60',
            'overtime_threshold_minutes'=> 'required|integer|min:0|max:120',
            'min_hours_for_full_day'    => 'required|integer|min:1|max:24',
            'half_day_threshold_hours'  => 'required|integer|min:1|max:12',
            'flexible_window_start'     => 'nullable|date_format:H:i',
            'flexible_window_end'       => 'nullable|date_format:H:i',
            'flexible_duration_hours'   => 'nullable|integer|min:1|max:24',
            'timezone'                  => 'required|string|max:50',
            'color'                     => 'required|string|size:7',
            'description'               => 'nullable|string|max:500',
            'is_active'                 => 'boolean',
        ]);
    }

    private function detectCrossesMidnight(string $start, string $end, bool $explicit): bool
    {
        return $explicit || $end < $start;
    }

    private function syncPattern(Shift $shift, Request $request): void
    {
        if ($shift->shift_type !== 'rotating') {
            return;
        }

        $cycleLength = (int) $request->input('cycle_length_days', 4);
        $pattern = $shift->patterns()->firstOrCreate(
            ['shift_id' => $shift->id],
            ['name' => $shift->name . ' Pattern', 'cycle_length_days' => $cycleLength]
        );
        $pattern->update(['cycle_length_days' => $cycleLength]);

        $workingDayNumbers = array_map('intval', (array) $request->input('pattern_working_days', []));

        $pattern->days()->delete();
        for ($i = 1; $i <= $cycleLength; $i++) {
            $pattern->days()->create([
                'day_number'    => $i,
                'is_working_day'=> in_array($i, $workingDayNumbers),
            ]);
        }
    }
}
