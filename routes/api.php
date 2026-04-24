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
