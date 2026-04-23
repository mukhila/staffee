<?php

namespace App\Services\HR;

use App\Models\HR\ExitChecklist;
use App\Models\HR\FinalSettlement;
use App\Models\HR\LifecycleEvent;
use App\Models\HR\PromotionRequest;
use App\Models\HR\ResignationRequest;
use App\Models\HR\SalaryRevision;
use App\Models\HR\TerminationRequest;
use App\Models\HR\TransferRequest;
use App\Models\HR\WarningRecord;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LifecycleEventService
{
    // ─── Promotion ────────────────────────────────────────────────────────────

    /**
     * Apply a fully-approved promotion:
     *  1. Update users row (role, department, designation)
     *  2. Write salary_revisions record
     *  3. Write lifecycle_events row
     *  4. Notify employee + team
     */
    public function applyPromotion(PromotionRequest $promotion, User $approvedBy): void
    {
        DB::transaction(function () use ($promotion, $approvedBy) {
            $employee = $promotion->employee;

            // 1. Mutate user
            $employee->update([
                'role'          => $promotion->proposed_role,
                'department_id' => $promotion->proposed_department_id,
                'designation'   => $promotion->proposed_designation,
            ]);

            // 2. Salary revision record
            if ($promotion->proposed_salary && $promotion->proposed_salary != $promotion->current_salary) {
                $pctChange = $promotion->current_salary > 0
                    ? round((($promotion->proposed_salary - $promotion->current_salary) / $promotion->current_salary) * 100, 2)
                    : null;

                SalaryRevision::create([
                    'user_id'         => $employee->id,
                    'effective_date'  => $promotion->effective_date,
                    'old_salary'      => $promotion->current_salary,
                    'new_salary'      => $promotion->proposed_salary,
                    'revision_type'   => 'promotion',
                    'percentage_change' => $pctChange,
                    'reason'          => "Promotion: {$promotion->current_designation} → {$promotion->proposed_designation}",
                    'reference_type'  => 'promotion_requests',
                    'reference_id'    => $promotion->id,
                    'approved_by'     => $approvedBy->id,
                    'created_by'      => $approvedBy->id,
                ]);

                // Keep profile in sync
                $employee->profile?->update(['current_salary' => $promotion->proposed_salary]);
            }

            // 3. Canonical timeline entry
            LifecycleEvent::create([
                'user_id'             => $employee->id,
                'event_type'          => 'promotion',
                'title'               => "Promoted to {$promotion->proposed_designation}",
                'description'         => $promotion->reason,
                'effective_date'      => $promotion->effective_date,
                'old_role'            => $promotion->current_role,
                'new_role'            => $promotion->proposed_role,
                'old_department_id'   => $promotion->current_department_id,
                'new_department_id'   => $promotion->proposed_department_id,
                'old_designation'     => $promotion->current_designation,
                'new_designation'     => $promotion->proposed_designation,
                'old_salary'          => $promotion->current_salary,
                'new_salary'          => $promotion->proposed_salary,
                'reference_type'      => 'promotion_requests',
                'reference_id'        => $promotion->id,
                'performed_by'        => $approvedBy->id,
            ]);

            // 4. Mark promotion as applied
            $promotion->update([
                'status'        => 'approved',
                'announced_at'  => now(),
            ]);

            // 5. Notifications
            $this->notify(
                $employee,
                'promotion',
                "Congratulations! You have been promoted to {$promotion->proposed_designation}.",
                route('staff.tasks.index')
            );
        });
    }

    // ─── Resignation ─────────────────────────────────────────────────────────

    /**
     * HR approves resignation → lock official last date → create termination.
     */
    public function approveResignation(ResignationRequest $resignation, User $hrUser): void
    {
        DB::transaction(function () use ($resignation, $hrUser) {
            $employee = $resignation->employee;

            // Calculate official last date
            $officialLastDate = $resignation->notice_waived
                ? $resignation->submitted_date
                : $resignation->submitted_date->addDays($resignation->notice_period_days);

            $resignation->update([
                'status'            => 'approved',
                'official_last_date' => $officialLastDate,
                'hr_reviewed_by'    => $hrUser->id,
                'hr_reviewed_at'    => now(),
            ]);

            // Switch employment status
            $employee->update(['employment_status' => 'notice_period']);

            // Lifecycle entry
            LifecycleEvent::create([
                'user_id'        => $employee->id,
                'event_type'     => 'resignation_accepted',
                'title'          => 'Resignation accepted',
                'description'    => "Last working date: {$officialLastDate->format('d M Y')}",
                'effective_date' => now()->toDateString(),
                'reference_type' => 'resignation_requests',
                'reference_id'   => $resignation->id,
                'performed_by'   => $hrUser->id,
            ]);

            // Auto-create termination record
            TerminationRequest::create([
                'user_id'          => $employee->id,
                'initiated_by'     => $hrUser->id,
                'termination_type' => 'voluntary_resignation',
                'reason'           => $resignation->reason,
                'last_working_date' => $officialLastDate,
                'resignation_id'   => $resignation->id,
                'status'           => 'approved',
            ]);

            $this->notify($employee, 'info',
                "Your resignation has been accepted. Last working date: {$officialLastDate->format('d M Y')}.",
                route('notifications.index')
            );
        });
    }

    // ─── Termination ─────────────────────────────────────────────────────────

    /**
     * Process completed termination:
     *  1. Generate exit checklist from default template
     *  2. Transition employee status
     *  3. Lifecycle entry
     */
    public function processTermination(TerminationRequest $termination, User $processedBy): void
    {
        DB::transaction(function () use ($termination, $processedBy) {
            $employee = $termination->employee;

            // Create exit checklist with default items
            $checklist = ExitChecklist::create([
                'termination_id' => $termination->id,
                'user_id'        => $employee->id,
            ]);

            $this->seedDefaultChecklistItems($checklist, $processedBy);

            $termination->update(['status' => 'processing']);

            LifecycleEvent::create([
                'user_id'        => $employee->id,
                'event_type'     => 'termination',
                'title'          => 'Employment termination initiated',
                'description'    => $termination->reason,
                'effective_date' => $termination->last_working_date,
                'reference_type' => 'termination_requests',
                'reference_id'   => $termination->id,
                'performed_by'   => $processedBy->id,
                'is_sensitive'   => true,
            ]);

            $this->notify($employee, 'warning',
                'Your termination process has been initiated. Please complete the exit checklist.',
                route('notifications.index')
            );
        });
    }

    /**
     * After checklist is complete: calculate settlement and lock employee out.
     */
    public function completeTermination(TerminationRequest $termination, User $completedBy): void
    {
        DB::transaction(function () use ($termination, $completedBy) {
            $employee = $termination->employee;

            $employee->update(['employment_status' => 'terminated', 'is_active' => false]);

            $termination->update([
                'status'             => 'settlement_pending',
                'settlement_status'  => 'calculating',
            ]);

            LifecycleEvent::create([
                'user_id'        => $employee->id,
                'event_type'     => 'termination',
                'title'          => 'Employment ended',
                'description'    => "Last working date: {$termination->last_working_date->format('d M Y')}",
                'effective_date' => $termination->last_working_date,
                'reference_type' => 'termination_requests',
                'reference_id'   => $termination->id,
                'performed_by'   => $completedBy->id,
                'is_sensitive'   => true,
            ]);
        });
    }

    // ─── Transfer ─────────────────────────────────────────────────────────────

    public function applyTransfer(TransferRequest $transfer, User $approvedBy): void
    {
        DB::transaction(function () use ($transfer, $approvedBy) {
            $employee = $transfer->employee;

            $employee->update([
                'department_id' => $transfer->to_department_id,
                'role'          => $transfer->to_role ?? $employee->role,
                'designation'   => $transfer->to_designation ?? $employee->designation,
                'reporting_to'  => $transfer->to_reporting_to ?? $employee->reporting_to,
            ]);

            $transfer->update(['status' => 'completed']);

            LifecycleEvent::create([
                'user_id'           => $employee->id,
                'event_type'        => 'transfer',
                'title'             => "Transferred to {$transfer->toDepartment->name}",
                'description'       => $transfer->reason,
                'effective_date'    => $transfer->effective_date,
                'old_department_id' => $transfer->from_department_id,
                'new_department_id' => $transfer->to_department_id,
                'old_role'          => $transfer->from_role,
                'new_role'          => $transfer->to_role,
                'old_designation'   => $transfer->from_designation,
                'new_designation'   => $transfer->to_designation,
                'reference_type'    => 'transfer_requests',
                'reference_id'      => $transfer->id,
                'performed_by'      => $approvedBy->id,
            ]);

            $this->notify($employee, 'info',
                "You have been transferred to {$transfer->toDepartment->name} effective {$transfer->effective_date->format('d M Y')}.",
                route('notifications.index')
            );
        });
    }

    // ─── Warning ──────────────────────────────────────────────────────────────

    public function issueWarning(WarningRecord $warning): void
    {
        LifecycleEvent::create([
            'user_id'        => $warning->user_id,
            'event_type'     => 'warning',
            'title'          => ucfirst($warning->warning_type) . ' warning issued',
            'description'    => $warning->reason,
            'effective_date' => $warning->incident_date,
            'reference_type' => 'warning_records',
            'reference_id'   => $warning->id,
            'performed_by'   => $warning->issued_by,
            'is_sensitive'   => true,
        ]);

        // Notify employee (sensitive — don't expose in public timeline)
        $this->notify($warning->user, 'warning',
            "A {$warning->warning_type} warning has been recorded against your profile. Please review and respond.",
            route('notifications.index')
        );
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function notify(User $user, string $type, string $message, string $url): void
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'type'    => $type,
                'title'   => 'HR Update',
                'message' => $message,
                'url'     => $url,
            ]);
        } catch (\Exception $e) {
            Log::warning("LifecycleEventService: failed to create notification — {$e->getMessage()}");
        }
    }

    private function seedDefaultChecklistItems(ExitChecklist $checklist, User $assignedTo): void
    {
        $defaults = [
            ['assets',             'Return company laptop',              10],
            ['assets',             'Return ID badge and access cards',   20],
            ['assets',             'Return any company mobile devices',  30],
            ['access',             'Revoke email account',               10],
            ['access',             'Revoke VPN access',                  20],
            ['access',             'Remove from GitHub / GitLab org',    30],
            ['access',             'Revoke cloud platform access',       40],
            ['knowledge_transfer', 'Hand over ongoing project tasks',    10],
            ['knowledge_transfer', 'Document system credentials/secrets',20],
            ['knowledge_transfer', 'Conduct knowledge transfer sessions',30],
            ['documentation',      'Issue relieving letter',             10],
            ['documentation',      'Issue experience letter',            20],
            ['documentation',      'Collect signed NDA/non-compete',     30],
            ['finance',            'Settle pending expense claims',       10],
            ['finance',            'Recover outstanding advances',        20],
            ['hr',                 'Conduct exit interview',              10],
            ['hr',                 'Collect PF/gratuity nomination form',20],
        ];

        foreach ($defaults as [$category, $item, $order]) {
            $checklist->items()->create([
                'category'            => $category,
                'item'                => $item,
                'responsible_user_id' => $assignedTo->id,
                'sort_order'          => $order,
            ]);
        }
    }
}
