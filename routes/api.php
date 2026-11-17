<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
| All routes below require a valid agent_token passed as:
|   Authorization: Bearer {token}
| The 'agent' middleware resolves the token to a User and attaches it via
| $request->agentUser().
|
| These routes are stateless (no CSRF, no session).
*/

/*
|--------------------------------------------------------------------------
| Staff API v1 (personal access tokens)
|--------------------------------------------------------------------------
| Authorization: Bearer {token}, created under Profile → API Tokens.
| Each person only ever sees and changes their own records.
| "read" tokens can call the GET routes; "write" tokens the rest.
*/
Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
    Route::middleware('abilities:read')->group(function () {
        Route::get('me', [\App\Http\Controllers\Api\V1\ProfileController::class, 'me'])->name('me');
        Route::get('announcements', [\App\Http\Controllers\Api\V1\ProfileController::class, 'announcements'])->name('announcements');
        Route::get('attendance/today', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'today'])->name('attendance.today');
        Route::get('attendance', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('leave/types', [\App\Http\Controllers\Api\V1\LeaveController::class, 'types'])->name('leave.types');
        Route::get('leave', [\App\Http\Controllers\Api\V1\LeaveController::class, 'index'])->name('leave.index');
        Route::get('payslips', [\App\Http\Controllers\Api\V1\PayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payslip}/pdf', [\App\Http\Controllers\Api\V1\PayslipController::class, 'pdf'])->name('payslips.pdf');
    });

    Route::middleware('abilities:write')->group(function () {
        Route::post('attendance/check-in', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('attendance/check-out', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'checkOut'])->name('attendance.check-out');
        Route::post('leave', [\App\Http\Controllers\Api\V1\LeaveController::class, 'store'])->name('leave.store');
        Route::delete('leave/{leave}', [\App\Http\Controllers\Api\V1\LeaveController::class, 'destroy'])->name('leave.destroy');
    });
});

Route::prefix('agent')->middleware('agent')->group(function () {
    // Session lifecycle
    Route::post('session/start', [AgentController::class, 'sessionStart'])->name('agent.session.start');
    Route::post('session/end',   [AgentController::class, 'sessionEnd'])->name('agent.session.end');

    // Keep-alive
    Route::post('heartbeat',  [AgentController::class, 'heartbeat'])->name('agent.heartbeat');

    // Config pull
    Route::get('config',      [AgentController::class, 'config'])->name('agent.config');

    // Data ingestion
    Route::post('screenshot', [AgentController::class, 'screenshot'])->name('agent.screenshot');
    Route::post('activity',   [AgentController::class, 'activity'])->name('agent.activity');
    Route::post('idle',       [AgentController::class, 'idle'])->name('agent.idle');
});
