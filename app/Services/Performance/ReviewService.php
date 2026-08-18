<?php

namespace App\Services\Performance;

use App\Models\Performance\PerformanceCycle;
use App\Models\Performance\PerformanceGoal;
use App\Models\Performance\PerformanceReview;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Create a review for an employee under a cycle.
     * Only admin/PM can initiate (caller must enforce that).
     */
    public function createReview(
        PerformanceCycle $cycle,
        int $revieweeId,
        int $reviewerId,
        int $actorId
    ): PerformanceReview {
        return PerformanceReview::create([
            'cycle_id'    => $cycle->id,
            'reviewee_id' => $revieweeId,
            'reviewer_id' => $reviewerId,
            'status'      => 'pending',
        ]);
    }

    /**
     * Employee submits self-assessment rating + comments + optional goal self-ratings.
     *
     * $data keys:
     *   self_rating    (numeric)
     *   self_comments  (string)
     *   goals          (array of ['id' => int, 'self_rating' => numeric])
     */
    public function submitSelfAssessment(PerformanceReview $review, array $data, int $actorId): void
    {
        if ($review->status !== 'pending') {
            throw new \InvalidArgumentException(
                "Self-assessment can only be submitted when the review is in 'pending' status. Current status: {$review->status}."
            );
        }

        DB::transaction(function () use ($review, $data) {
            $review->update([
                'self_rating'   => $data['self_rating'] ?? null,
                'self_comments' => $data['self_comments'] ?? null,
                'status'        => 'self_submitted',
            ]);

            if (!empty($data['goals'])) {
                foreach ($data['goals'] as $goalData) {
                    PerformanceGoal::where('id', $goalData['id'])
                        ->where('review_id', $review->id)
                        ->update(['self_rating' => $goalData['self_rating'] ?? null]);
                }
            }
        });
    }

    /**
     * Manager submits final ratings, comments, and goal outcomes.
     *
     * $data keys:
     *   overall_rating    (numeric)
     *   overall_comments  (string)
     *   goals             (array of ['id', 'reviewer_rating', 'achievement_notes', 'status'])
     */
    public function submitManagerReview(PerformanceReview $review, array $data, int $actorId): void
    {
        if (!in_array($review->status, ['self_submitted', 'manager_reviewing', 'hr_calibrated'])) {
            throw new \InvalidArgumentException(
                "Manager review can only be submitted from 'self_submitted', 'manager_reviewing', or 'hr_calibrated' status. Current status: {$review->status}."
            );
        }

        DB::transaction(function () use ($review, $data) {
            $review->update([
                'overall_rating'   => $data['overall_rating'] ?? null,
                'overall_comments' => $data['overall_comments'] ?? null,
                'status'           => 'completed',
                'submitted_at'     => now(),
                'completed_at'     => now(),
            ]);

            if (!empty($data['goals'])) {
                foreach ($data['goals'] as $goalData) {
                    $attributes = array_filter([
                        'reviewer_rating'   => $goalData['reviewer_rating'] ?? null,
                        'achievement_notes' => $goalData['achievement_notes'] ?? null,
                        'status'            => $goalData['status'] ?? null,
                    ], fn ($v) => $v !== null);

                    if (!empty($attributes)) {
                        PerformanceGoal::where('id', $goalData['id'])
                            ->where('review_id', $review->id)
                            ->update($attributes);
                    }
                }
            }
        });
    }

    /**
     * Employee acknowledges their completed review.
     */
    public function acknowledgeReview(PerformanceReview $review, int $actorId): void
    {
        if ($review->status !== 'completed') {
            throw new \InvalidArgumentException(
                "A review can only be acknowledged once it is 'completed'. Current status: {$review->status}."
            );
        }

        $review->update([
            'acknowledged_by_employee' => true,
            'acknowledged_at'          => now(),
        ]);
    }
}
