<?php

namespace App\Services\HR;

use App\Models\HR\OnboardingTask;

class OnboardingService
{
    /**
     * Mark a task as done and check whether all tasks are complete to update
     * the parent checklist status.
     */
    public function completeTask(OnboardingTask $task, int $actorId): void
    {
        $task->update([
            'status'       => 'done',
            'completed_at' => now(),
            'completed_by' => $actorId,
        ]);

        $checklist = $task->checklist;

        // Reload tasks fresh from DB to get accurate counts
        $allTasks      = $checklist->tasks()->get();
        $pendingTasks  = $allTasks->whereNotIn('status', ['done', 'skipped']);

        if ($pendingTasks->isEmpty()) {
            $checklist->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        } elseif ($checklist->status === 'pending') {
            $checklist->update(['status' => 'in_progress']);
        }
    }
}
