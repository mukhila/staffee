<?php

namespace App\Services\HR;

use App\Models\HR\FinalSettlement;
use App\Models\HR\TerminationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TerminationService
{
    /**
     * Build the draft settlement record.
     * All monetary inputs come from employee_profiles.current_salary;
     * leave encashment uses leave_requests to count unused approved days.
     */
    public function calculateSettlement(TerminationRequest $termination): FinalSettlement
    {
        $employee  = $termination->employee->load('profile');
        $lastDate  = $termination->last_working_date;
        $profile   = $employee->profile;

        // ── Pending salary ────────────────────────────────────────────────────
        $basicSalary   = (float) ($profile?->current_salary ?? 0);
        $dailyRate     = $basicSalary > 0 ? round($basicSalary / 30, 2) : 0;
        $pendingDays   = $lastDate->day; // days worked in final month
        $pendingSalary = round($dailyRate * $pendingDays, 2);

        // ── Leave encashment ─────────────────────────────────────────────────
        // Count approved annual/casual leave days taken this year, subtract from standard entitlement (21 days)
        $leavesTaken = \App\Models\LeaveRequest::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('from_date', $lastDate->year)
            ->whereIn('type', ['annual', 'casual'])
            ->sum('days');
        $annualEntitlement  = 21;
        $unusedLeaveDays    = max(0, $annualEntitlement - $leavesTaken);
        $leaveEncashAmount  = round($dailyRate * $unusedLeaveDays, 2);

        // ── Gratuity ─────────────────────────────────────────────────────────
        // Statutory formula: (15/26) × basic_salary × years_of_service (if ≥ 5 years)
        $yearsOfService = $profile?->joining_date
            ? round($profile->joining_date->diffInYears($lastDate), 0)
            : 0;
        $gratuity = $yearsOfService >= 5
            ? round((15 / 26) * $basicSalary * $yearsOfService, 2)
            : 0;

        // ── Notice shortfall ──────────────────────────────────────────────────
        $requiredNoticeDays = $profile?->notice_period_days ?? 30;
        $actualNoticeDays   = max(0, now()->diffInDays($lastDate, false));
        $shortfall          = max(0, $requiredNoticeDays - $actualNoticeDays);
        $shortfallDeduction = round($dailyRate * $shortfall, 2);

        // ── Totals ────────────────────────────────────────────────────────────
        $totalEarnings   = $pendingSalary + $leaveEncashAmount + $gratuity;
        $totalDeductions = $shortfallDeduction;
        $netPayable      = max(0, $totalEarnings - $totalDeductions);

        return DB::transaction(function () use (
            $termination, $employee, $lastDate, $basicSalary,
            $pendingDays, $pendingSalary, $unusedLeaveDays, $leaveEncashAmount,
            $gratuity, $shortfall, $shortfallDeduction,
            $totalEarnings, $totalDeductions, $netPayable,
            $profile
        ) {
            // Delete any prior draft so we can recalculate cleanly
            $termination->settlement?->delete();

            $settlement = FinalSettlement::create([
                'termination_id'             => $termination->id,
                'user_id'                    => $employee->id,
                'last_working_date'          => $lastDate,
                'basic_salary'               => $basicSalary,
                'pending_salary_days'        => $pendingDays,
                'pending_salary_amount'      => $pendingSalary,
                'leave_encashment_days'      => $unusedLeaveDays,
                'leave_encashment_amount'    => $leaveEncashAmount,
                'gratuity'                   => $gratuity,
                'notice_shortfall_days'      => $shortfall,
                'notice_shortfall_deduction' => $shortfallDeduction,
                'total_earnings'             => $totalEarnings,
                'total_deductions'           => $totalDeductions,
                'net_payable'                => $netPayable,
                'currency'                   => $profile?->salary_currency ?? 'USD',
                'status'                     => 'pending_approval',
                'calculated_by'              => auth()->id(),
            ]);

            $termination->update(['settlement_status' => 'pending_approval']);

            return $settlement;
        });
    }

    /**
     * Remove the employee from all active projects and tasks after termination.
     */
    public function offboardEmployee(User $employee): void
    {
        DB::transaction(function () use ($employee) {
            // Detach from all projects
            $employee->projects()->detach();

            // Unassign open tasks
            \App\Models\Task::where('assigned_to', $employee->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->update(['assigned_to' => null]);

            // Transfer direct reports to the offboarded employee's manager
            User::where('reporting_to', $employee->id)
                ->update(['reporting_to' => $employee->reporting_to]);
        });
    }
}
