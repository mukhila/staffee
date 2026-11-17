<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ReportSubscription;
use App\Notifications\ScheduledReportNotification;
use App\Services\Reports\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Download reports on demand, or subscribe to have them emailed weekly or monthly.
 */
class ScheduledReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request)
    {
        $user      = $request->user();
        $available = $this->reports->availableTo($user);
        abort_if(empty($available), 403, 'You do not have access to any reports.');

        return view('reports.scheduled', [
            'available'     => $available,
            'subscriptions' => ReportSubscription::with('department:id,name')->where('user_id', $user->id)->orderBy('report')->get(),
            'departments'   => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'periods'       => ReportService::PERIODS,
            'frequencies'   => ReportSubscription::FREQUENCIES,
            'all'           => $this->reports->all(),
        ]);
    }

    public function download(Request $request)
    {
        $user      = $request->user();
        $available = $this->reports->availableTo($user);

        $data = $request->validate([
            'report'        => ['required', Rule::in(array_keys($available))],
            'period'        => ['required', Rule::in(array_keys(ReportService::PERIODS))],
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        [$from, $to, $range] = $this->reports->period($data['period']);
        $built = $this->reports->build($available[$data['report']], $user, $from, $to, $range, $data['department_id'] ?? null);

        return response($built['csv'], 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $built['filename'] . '"',
        ]);
    }

    public function store(Request $request)
    {
        $user      = $request->user();
        $available = $this->reports->availableTo($user);

        $data = $request->validate([
            'report'        => ['required', Rule::in(array_keys($available))],
            'frequency'     => ['required', Rule::in(array_keys(ReportSubscription::FREQUENCIES))],
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        if (!$available[$data['report']]->supportsDepartment()) {
            $data['department_id'] = null;
        }

        $sub = ReportSubscription::firstOrNew([
            'user_id'       => $user->id,
            'report'        => $data['report'],
            'frequency'     => $data['frequency'],
            'department_id' => $data['department_id'] ?? null,
        ]);

        if ($sub->exists && $sub->is_active) {
            return back()->with('error', 'You are already subscribed to that report.');
        }

        $sub->fill(['is_active' => true, 'last_error' => null])->save();

        return back()->with('success', "Subscribed. The first email goes out on {$sub->nextSendDate()->format('l j F')} to {$user->email}.");
    }

    public function toggle(Request $request, ReportSubscription $subscription)
    {
        $this->authorizeOwner($request, $subscription);

        $subscription->update(['is_active' => !$subscription->is_active, 'last_error' => null]);

        return back()->with('success', $subscription->is_active ? 'Subscription turned back on.' : 'Subscription paused.');
    }

    public function destroy(Request $request, ReportSubscription $subscription)
    {
        $this->authorizeOwner($request, $subscription);
        $subscription->delete();

        return back()->with('success', 'Subscription removed.');
    }

    /** Email the most recent completed period now, to check what the email looks like. */
    public function sendNow(Request $request, ReportSubscription $subscription)
    {
        $this->authorizeOwner($request, $subscription);
        $user = $request->user();

        try {
            $report = $this->reports->find($subscription->report);
            [$from, $to, $range] = $this->reports->period($subscription->period());
            $built = $this->reports->build($report, $user, $from, $to, $range, $subscription->department_id);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $user->notify(new ScheduledReportNotification($built));

        return back()->with('success', "Sent to {$user->email}. If it does not arrive, check that outgoing email is set up.");
    }

    private function authorizeOwner(Request $request, ReportSubscription $subscription): void
    {
        abort_unless($subscription->user_id === $request->user()->id, 404);
    }
}
