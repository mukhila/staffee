<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\HR\TerminationRequest;
use App\Models\User;
use App\Notifications\SettlementSlipGeneratedNotification;
use App\Services\Payroll\SettlementService;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(
        private readonly SettlementService $settlementService,
    ) {}

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'last_working_date' => 'required|date',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $preview = $this->settlementService->calculateFullAndFinal($user, $validated['last_working_date']);

        return view('admin.payroll.settlements.preview', compact('user', 'preview'));
    }

    public function finalize(TerminationRequest $termination)
    {
        $settlement = $this->settlementService->generateSettlementSlip($termination->employee, $termination);
        $termination->employee->notify(new SettlementSlipGeneratedNotification($settlement));

        return redirect()->route('admin.hr.terminations.show', $termination)
            ->with('success', 'Settlement processed and slip generated successfully.');
    }
}
