<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LeaveController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveSvc,
        private readonly LeaveBalanceService $balanceSvc,
    ) {}

    /** GET /api/v1/leave/types — active leave types with this year's balance */
    public function types(Request $request): JsonResponse
    {
        $balances = $this->balanceSvc->getBalanceSummary($request->user(), now()->year);

        $types = LeaveType::active()->orderBy('name')->get()->map(fn (LeaveType $type) => [
            'id'             => $type->id,
            'name'           => $type->name,
            'code'           => $type->code,
            'is_paid'        => (bool) $type->is_paid,
            'allow_half_day' => (bool) $type->allow_half_day,
            'available_days' => isset($balances[$type->id]) ? (float) $balances[$type->id]->effective_available : null,
            'pending_days'   => isset($balances[$type->id]) ? (float) $balances[$type->id]->pending_days : 0.0,
        ]);

        return response()->json(['data' => $types, 'meta' => ['year' => now()->year]]);
    }

    /** GET /api/v1/leave */
    public function index(Request $request): JsonResponse
    {
        $leaves = LeaveRequest::with('leaveType:id,name')
            ->forUser($request->user()->id)
            ->orderByDesc('from_date')
            ->limit(100)
            ->get();

        return response()->json(['data' => $leaves->map(fn (LeaveRequest $l) => self::present($l))]);
    }

    /** POST /api/v1/leave */
    public function store(Request $request): JsonResponse
    {
        // Same rules as the web form.
        $data = $request->validate([
            'leave_type_id'   => 'required|exists:leave_types,id',
            'from_date'       => 'required|date|after_or_equal:today',
            'to_date'         => 'required|date|after_or_equal:from_date',
            'half_day'        => 'boolean',
            'half_day_period' => 'nullable|in:morning,afternoon',
            'reason'          => 'required|string|max:1000',
        ]);

        try {
            $leave = $this->leaveSvc->submit($request->user(), $data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $clashes = $this->leaveSvc->teamClashes($request->user(), $leave->from_date, $leave->to_date, $leave->id);

        return response()->json([
            'message' => 'Leave request submitted.',
            'data'    => self::present($leave->load('leaveType:id,name')),
            'meta'    => ['team_members_on_leave' => $clashes->pluck('user.name')->filter()->unique()->values()],
        ], 201);
    }

    /** DELETE /api/v1/leave/{leave} — cancel */
    public function destroy(Request $request, LeaveRequest $leave): JsonResponse
    {
        if ($leave->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        try {
            $this->leaveSvc->cancel($leave, 'Cancelled by employee (API)');
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Leave request cancelled.', 'data' => self::present($leave->fresh('leaveType'))]);
    }

    public static function present(LeaveRequest $l): array
    {
        return [
            'id'         => $l->id,
            'type'       => $l->leaveType?->name,
            'from_date'  => $l->from_date?->toDateString(),
            'to_date'    => $l->to_date?->toDateString(),
            'days'       => (float) $l->days,
            'half_day'   => (bool) $l->half_day,
            'status'     => $l->status,
            'reason'     => $l->reason,
            'created_at' => $l->created_at?->toIso8601String(),
        ];
    }
}
