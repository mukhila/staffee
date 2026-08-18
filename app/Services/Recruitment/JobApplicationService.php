<?php

namespace App\Services\Recruitment;

use App\Models\HR\OnboardingChecklist;
use App\Models\HR\OnboardingTask;
use App\Models\Recruitment\JobApplication;
use App\Models\Recruitment\JobPosting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class JobApplicationService
{
    /** Valid status transitions for the pipeline */
    private const TRANSITIONS = [
        'new'                  => ['screening', 'rejected', 'withdrawn'],
        'screening'            => ['interview_scheduled', 'rejected', 'withdrawn'],
        'interview_scheduled'  => ['interviewed', 'rejected', 'withdrawn'],
        'interviewed'          => ['offer_sent', 'rejected', 'withdrawn'],
        'offer_sent'           => ['hired', 'rejected', 'withdrawn'],
        'hired'                => [],
        'rejected'             => [],
        'withdrawn'            => [],
    ];

    /** Standard onboarding task titles created for every new hire */
    private const STANDARD_TASKS = [
        ['title' => 'Send welcome email with login credentials', 'sort_order' => 1],
        ['title' => 'Collect signed offer letter',              'sort_order' => 2],
        ['title' => 'Set up workstation / equipment',           'sort_order' => 3],
        ['title' => 'Complete HR paperwork & contracts',        'sort_order' => 4],
        ['title' => 'IT account provisioning',                  'sort_order' => 5],
        ['title' => 'Introduce to team members',                'sort_order' => 6],
        ['title' => 'Assign buddy / mentor',                    'sort_order' => 7],
        ['title' => 'Complete company policy orientation',      'sort_order' => 8],
    ];

    /**
     * Create a new job application, optionally storing a resume file.
     */
    public function applyToJob(JobPosting $posting, array $data): JobApplication
    {
        if ($posting->status !== 'open') {
            throw new InvalidArgumentException('This job posting is not accepting applications.');
        }

        $resumePath = null;
        if (!empty($data['resume']) && $data['resume'] instanceof UploadedFile) {
            $resumePath = $data['resume']->store('resumes', 'local');
        }

        return JobApplication::create([
            'job_posting_id'      => $posting->id,
            'applicant_name'      => $data['applicant_name'],
            'applicant_email'     => $data['applicant_email'],
            'applicant_phone'     => $data['applicant_phone'] ?? null,
            'resume_path'         => $resumePath,
            'cover_letter'        => $data['cover_letter'] ?? null,
            'source'              => $data['source'] ?? null,
            'referred_by_user_id' => $data['referred_by_user_id'] ?? null,
            'status'              => 'new',
            'applied_at'          => now(),
        ]);
    }

    /**
     * Advance an application to a new status (validates allowed transitions).
     */
    public function advanceStatus(JobApplication $app, string $newStatus, int $actorId): void
    {
        $allowed = self::TRANSITIONS[$app->status] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$app->status}' to '{$newStatus}'."
            );
        }

        $app->update(['status' => $newStatus]);
    }

    /**
     * Mark applicant as hired and create a standard onboarding checklist.
     */
    public function hireApplicant(JobApplication $app, int $actorId): OnboardingChecklist
    {
        // Allow transition from offer_sent → hired or directly from interviewed
        $allowed = self::TRANSITIONS[$app->status] ?? [];
        if (!in_array('hired', $allowed, true) && $app->status !== 'hired') {
            throw new InvalidArgumentException(
                "Cannot hire applicant from status '{$app->status}'."
            );
        }

        $app->update(['status' => 'hired']);

        // We need a user account for the hire — look up by email or use a placeholder user_id.
        // In this flow the admin will link the user account manually; we use actorId as fallback.
        $userId = \App\Models\User::where('email', $app->applicant_email)->value('id') ?? $actorId;

        $checklist = OnboardingChecklist::create([
            'user_id'       => $userId,
            'template_name' => 'Standard Onboarding',
            'due_date'      => now()->addDays(30)->toDateString(),
            'status'        => 'pending',
        ]);

        foreach (self::STANDARD_TASKS as $task) {
            OnboardingTask::create([
                'checklist_id' => $checklist->id,
                'title'        => $task['title'],
                'sort_order'   => $task['sort_order'],
                'status'       => 'pending',
            ]);
        }

        return $checklist;
    }
}
