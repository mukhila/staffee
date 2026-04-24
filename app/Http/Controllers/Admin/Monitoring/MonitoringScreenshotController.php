<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Monitoring\MonitoringScreenshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MonitoringScreenshotController extends Controller
{
    /** Screenshot gallery for a user, filterable by date. */
    public function index(User $user, Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();

        $screenshots = MonitoringScreenshot::where('user_id', $user->id)
            ->whereDate('captured_at', $date)
            ->orderBy('captured_at')
            ->paginate(24)
            ->withQueryString();

        $availableDates = MonitoringScreenshot::where('user_id', $user->id)
            ->selectRaw('DATE(captured_at) as day')
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(30)
            ->pluck('day');

        return view('admin.monitoring.screenshots', compact(
            'user', 'date', 'screenshots', 'availableDates'
        ));
    }

    /** Flag or unflag a screenshot. */
    public function flag(MonitoringScreenshot $screenshot, Request $request)
    {
        $data = $request->validate([
            'flag_reason' => 'nullable|string|max:255',
        ]);

        $screenshot->update([
            'is_flagged'  => !$screenshot->is_flagged,
            'flag_reason' => $screenshot->is_flagged ? null : ($data['flag_reason'] ?? null),
        ]);

        return back()->with('success', $screenshot->is_flagged ? 'Screenshot unflagged.' : 'Screenshot flagged.');
    }

    /** Delete a screenshot and its stored file. */
    public function destroy(MonitoringScreenshot $screenshot)
    {
        Storage::disk('public')->delete($screenshot->file_path);
        if ($screenshot->thumbnail_path) {
            Storage::disk('public')->delete($screenshot->thumbnail_path);
        }
        $screenshot->delete();
        return back()->with('success', 'Screenshot deleted.');
    }
}
