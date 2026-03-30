<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Bug;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $today = now()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();

        $pendingTasks = Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $assignedBugs = Bug::where('assigned_to', $user->id)
            ->where('status', '!=', 'closed')
            ->orderBy('severity', 'desc')
            ->limit(5)
            ->get();

        $workingHours = null;
        if ($attendance && $attendance->check_out) {
            $diff = \Carbon\Carbon::parse($attendance->check_in)->diff(\Carbon\Carbon::parse($attendance->check_out));
            $workingHours = $diff->format('%H:%I:%S');
        }

        // Announcements visible to this user
        $announcements = Announcement::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('audience', 'all')->orWhere('audience', $user->role);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Admin-only stats (real data)
        $adminStats = null;
        if ($user->isAdmin()) {
            $adminStats = [
                'total_staff'    => User::where('role', '!=', 'admin')->count(),
                'active_projects'=> Project::where('status', 'active')->count(),
                'on_leave'       => Attendance::where('date', $today)->where('status', 'leave')->count(),
                'open_bugs'      => Bug::where('status', 'open')->count(),
                'pending_tasks'  => Task::where('status', 'pending')->count(),
            ];
        }

        return view('dashboard', compact(
            'attendance', 'pendingTasks', 'assignedBugs',
            'workingHours', 'announcements', 'adminStats'
        ));
    }
}
