<?php

namespace App\Http\Controllers\Admin\Shift;

use App\Http\Controllers\Controller;
use App\Models\Shift\ShiftHoliday;
use Illuminate\Http\Request;

class ShiftHolidayController extends Controller
{
    public function index()
    {
        $holidays = ShiftHoliday::orderBy('date')->paginate(25);
        return view('admin.shifts.holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:150',
            'date'         => 'required|date',
            'holiday_type' => 'required|in:national,regional,company',
            'description'  => 'nullable|string|max:500',
            'is_recurring' => 'boolean',
        ]);

        ShiftHoliday::create([
            'name'         => $request->name,
            'date'         => $request->date,
            'holiday_type' => $request->holiday_type,
            'is_recurring' => $request->boolean('is_recurring'),
            'description'  => $request->description,
            'is_active'    => true,
        ]);

        return redirect()->route('admin.shifts.holidays.index')->with('success', 'Holiday added.');
    }

    public function destroy(ShiftHoliday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday removed.');
    }
}
