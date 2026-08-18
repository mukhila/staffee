<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceReview;
use App\Services\Performance\ReviewService;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function __construct(private ReviewService $service) {}

    public function index()
    {
        $reviews = PerformanceReview::with(['cycle', 'reviewer'])
            ->where('reviewee_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('staff.performance.index', compact('reviews'));
    }

    public function show(PerformanceReview $review)
    {
        abort_unless($review->reviewee_id === auth()->id(), 403);

        $review->load(['cycle', 'reviewer', 'goals']);

        return view('staff.performance.show', compact('review'));
    }

    public function selfAssessment(Request $request, PerformanceReview $review)
    {
        abort_unless($review->reviewee_id === auth()->id(), 403);

        $validated = $request->validate([
            'self_rating'       => 'required|numeric|min:0|max:5',
            'self_comments'     => 'nullable|string',
            'goals'             => 'nullable|array',
            'goals.*.id'        => 'required|integer|exists:performance_goals,id',
            'goals.*.self_rating' => 'nullable|numeric|min:0|max:5',
        ]);

        try {
            $this->service->submitSelfAssessment($review, $validated, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Self-assessment submitted successfully.');
    }

    public function acknowledge(PerformanceReview $review)
    {
        abort_unless($review->reviewee_id === auth()->id(), 403);

        try {
            $this->service->acknowledgeReview($review, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Review acknowledged.');
    }
}
