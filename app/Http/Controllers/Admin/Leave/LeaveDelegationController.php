<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveApprovalDelegation;
use App\Models\User;
use App\Services\Leave\LeaveApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LeaveDelegationController extends Controller
{
    public function __construct(private readonly LeaveApprovalService $approvalSvc) {}

    public function index()
    {
        $user = auth()->user();

        $mine = LeaveApprovalDelegation::with('delegate:id,name')
            ->where('delegator_id', $user->id)
            ->orderByDesc('from_date')
            ->get();

        $toMe = LeaveApprovalDelegation::with('delegator:id,name')
            ->where('delegate_id', $user->id)
            ->whereDate('to_date', '>=', Carbon::today()->toDateString())
            ->orderBy('from_date')
            ->get();

        // Only people who can actually approve leave are offered as delegates.
        $candidates = User::where('is_active', true)
            ->where('id', '!=', $user->id)
            ->whereIn('role', ['admin', 'pm'])
            ->orderBy('name')
            ->get(['id', 'name', 'role'])
            ->filter(fn (User $candidate) => $candidate->hasPermission('approve-leave'))
            ->values();

        return view('admin.leaves.delegations', compact('mine', 'toMe', 'candidates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'delegate_id' => 'required|exists:users,id',
            'from_date'   => 'required|date|after_or_equal:today',
            'to_date'     => 'required|date|after_or_equal:from_date',
            'reason'      => 'nullable|string|max:255',
        ]);

        try {
            $this->approvalSvc->delegate(
                $request->user(),
                User::findOrFail($data['delegate_id']),
                Carbon::parse($data['from_date']),
                Carbon::parse($data['to_date']),
                $data['reason'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Leave approvals delegated.');
    }

    public function destroy(LeaveApprovalDelegation $delegation)
    {
        abort_unless($delegation->delegator_id === auth()->id() || auth()->user()->isAdmin(), 403);

        $delegation->delete();

        return back()->with('success', 'Delegation removed.');
    }
}
