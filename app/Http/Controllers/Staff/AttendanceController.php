<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Shift\AttendanceClockService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceClockService $clock) {}

    public function checkIn(Request $request)
    {
        try {
            $this->clock->checkIn($request->user(), $request->input('latitude'), $request->input('longitude'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Checked in successfully.');
    }

    public function checkOut(Request $request)
    {
        try {
            $this->clock->checkOut($request->user(), $request->input('latitude'), $request->input('longitude'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Checked out successfully.');
    }
}
