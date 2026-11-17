<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Shift\AttendanceClockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceClockService $clock) {}

    /** GET /api/v1/attendance/today */
    public function today(Request $request): JsonResponse
    {
        $attendance = $this->clock->today($request->user());

        return response()->json([
            'data' => $attendance ? self::present($attendance) : null,
            'meta' => ['location_required' => $this->clock->geofenceRequired()],
        ]);
    }

    /** GET /api/v1/attendance?month=2026-11 */
    public function index(Request $request): JsonResponse
    {
        $data  = $request->validate(['month' => 'nullable|date_format:Y-m']);
        $month = isset($data['month']) ? Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth() : now()->startOfMonth();

        $rows = Attendance::where('user_id', $request->user()->id)
            ->whereDate('date', '>=', $month->toDateString())
            ->whereDate('date', '<=', $month->copy()->endOfMonth()->toDateString())
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => $rows->map(fn (Attendance $a) => self::present($a)),
            'meta' => ['month' => $month->format('Y-m')],
        ]);
    }

    /** POST /api/v1/attendance/check-in  {latitude?, longitude?} */
    public function checkIn(Request $request): JsonResponse
    {
        $this->validateLocation($request);

        try {
            $attendance = $this->clock->checkIn($request->user(), $request->input('latitude'), $request->input('longitude'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Checked in.', 'data' => self::present($attendance)], 201);
    }

    /** POST /api/v1/attendance/check-out  {latitude?, longitude?} */
    public function checkOut(Request $request): JsonResponse
    {
        $this->validateLocation($request);

        try {
            $attendance = $this->clock->checkOut($request->user(), $request->input('latitude'), $request->input('longitude'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Checked out.', 'data' => self::present($attendance)]);
    }

    private function validateLocation(Request $request): void
    {
        $request->validate([
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
    }

    public static function present(Attendance $a): array
    {
        return [
            'date'             => Carbon::parse($a->date)->toDateString(),
            'status'           => $a->status,
            'check_in'         => $a->check_in,
            'check_out'        => $a->check_out,
            'worked_minutes'   => $a->worked_minutes,
            'overtime_minutes' => $a->overtime_minutes,
        ];
    }
}
