<?php

namespace App\Services\Learning;

use App\Models\Learning\EmployeeEnrollment;
use App\Models\Learning\LearningCourse;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class LearningService
{
    /**
     * Enroll an employee in a course.
     */
    public function enroll(LearningCourse $course, User $user, int $actorId): EmployeeEnrollment
    {
        $exists = EmployeeEnrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('User is already enrolled in this course.');
        }

        return EmployeeEnrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'enrolled_at' => Carbon::now(),
            'status'      => 'enrolled',
            'enrolled_by' => $actorId !== $user->id ? $actorId : null,
        ]);
    }

    /**
     * Mark course as in_progress.
     */
    public function startCourse(EmployeeEnrollment $enrollment, int $actorId): void
    {
        if (in_array($enrollment->status, ['completed', 'dropped', 'failed'])) {
            throw new \InvalidArgumentException(
                "Cannot start a course that is already {$enrollment->status}."
            );
        }

        $enrollment->update(['status' => 'in_progress']);
    }

    /**
     * Complete a course with optional score and certificate upload.
     */
    public function completeCourse(EmployeeEnrollment $enrollment, array $data, int $actorId): void
    {
        if (! in_array($enrollment->status, ['enrolled', 'in_progress'])) {
            throw new \InvalidArgumentException(
                "Cannot complete a course with status '{$enrollment->status}'."
            );
        }

        $certificatePath = null;
        if (isset($data['certificate']) && $data['certificate'] instanceof UploadedFile) {
            $certificatePath = $data['certificate']->store('certificates', 'local');
        }

        $enrollment->update([
            'status'           => 'completed',
            'completed_at'     => Carbon::now(),
            'completion_score' => $data['completion_score'] ?? null,
            'certificate_path' => $certificatePath,
        ]);
    }

    /**
     * Drop a course.
     */
    public function dropCourse(EmployeeEnrollment $enrollment, int $actorId): void
    {
        $enrollment->update(['status' => 'dropped']);
    }
}
