<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = now()->format('Y-m-d');
        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $pendingTasks = \App\Models\Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $assignedBugs = \App\Models\Bug::where('assigned_to', $user->id)
            ->where('status', '!=', 'closed')
            ->orderBy('severity', 'desc')
            ->limit(5)
            ->get();

        $workingHours = null;
        if ($attendance && $attendance->check_out) {
            $checkIn = \Carbon\Carbon::parse($attendance->check_in);
            $checkOut = \Carbon\Carbon::parse($attendance->check_out);
            $diff = $checkIn->diff($checkOut);
            $workingHours = $diff->format('%H:%I:%S');
        }

        return view('dashboard', compact('attendance', 'pendingTasks', 'assignedBugs', 'workingHours'));
    }
}
