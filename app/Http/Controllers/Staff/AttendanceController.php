<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function checkIn()
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            return redirect()->back()->with('error', 'You have already checked in today.');
        }

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'check_in' => now()->format('H:i:s'),
            'status' => 'present',
        ]);

        return redirect()->back()->with('success', 'Checked in successfully.');
    }

    public function checkOut()
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');

        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'You have not checked in today.');
        }

        if ($attendance->check_out) {
            return redirect()->back()->with('error', 'You have already checked out today.');
        }

        $attendance->update([
            'check_out' => now()->format('H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Checked out successfully.');
    }
}
