<?php

namespace App\Http\Controllers\Admin\Performance;

use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceCycle;
use App\Models\Performance\PerformanceReview;
use App\Services\Performance\ReviewService;
use Illuminate\Http\Request;

class PerformanceReviewController extends Controller
{
    public function __construct(private ReviewService $service) {}

    public function store(Request $request, PerformanceCycle $cycle)
    {
        $validated = $request->validate([
            'reviewee_id' => 'required|exists:users,id',
            'reviewer_id' => 'required|exists:users,id',
        ]);

        $review = $this->service->createReview(
            $cycle,
            (int) $validated['reviewee_id'],
            (int) $validated['reviewer_id'],
            auth()->id()
        );

        return redirect()
            ->route('admin.performance.reviews.show', $review)
            ->with('success', 'Review created successfully.');
    }

    public function show(PerformanceReview $review)
    {
        $review->load(['cycle', 'reviewee', 'reviewer', 'department', 'goals']);

        return view('admin.performance.reviews.show', compact('review'));
    }

    public function submit(Request $request, PerformanceReview $review)
    {
        $validated = $request->validate([
            'overall_rating'    => 'required|numeric|min:0|max:5',
            'overall_comments'  => 'nullable|string',
            'goals'             => 'nullable|array',
            'goals.*.id'        => 'required|integer|exists:performance_goals,id',
            'goals.*.reviewer_rating'   => 'nullable|numeric|min:0|max:5',
            'goals.*.achievement_notes' => 'nullable|string',
            'goals.*.status'            => 'nullable|in:not_started,in_progress,achieved,partially_achieved,not_achieved',
        ]);

        try {
            $this->service->submitManagerReview($review, $validated, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.performance.reviews.show', $review)
            ->with('success', 'Review submitted successfully.');
    }
}
