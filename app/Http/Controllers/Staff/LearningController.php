<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Learning\EmployeeEnrollment;
use App\Models\Learning\LearningCourse;
use App\Services\Learning\LearningService;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    public function __construct(private LearningService $learningService) {}

    public function index()
    {
        $enrollments = EmployeeEnrollment::with('course')
            ->where('user_id', auth()->id())
            ->latest('enrolled_at')
            ->get();

        $availableCourses = LearningCourse::active()
            ->whereDoesntHave('enrollments', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->orderBy('title')
            ->get();

        return view('staff.learning.index', compact('enrollments', 'availableCourses'));
    }

    public function show(EmployeeEnrollment $enrollment)
    {
        abort_unless($enrollment->user_id === auth()->id(), 403);
        $enrollment->load('course');

        return view('staff.learning.show', compact('enrollment'));
    }

    public function enroll(Request $request, LearningCourse $course)
    {
        $user = auth()->user();

        try {
            $this->learningService->enroll($course, $user, $user->id);
            return redirect()->route('staff.learning.index')->with('success', 'Enrolled successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function start(Request $request, EmployeeEnrollment $enrollment)
    {
        abort_unless($enrollment->user_id === auth()->id(), 403);

        try {
            $this->learningService->startCourse($enrollment, auth()->id());
            return redirect()->route('staff.learning.show', $enrollment)->with('success', 'Course started.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request, EmployeeEnrollment $enrollment)
    {
        abort_unless($enrollment->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'completion_score' => 'nullable|numeric|min:0|max:100',
            'certificate'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $this->learningService->completeCourse($enrollment, [
                'completion_score' => $validated['completion_score'] ?? null,
                'certificate'      => $request->file('certificate'),
            ], auth()->id());
            return redirect()->route('staff.learning.show', $enrollment)->with('success', 'Course completed!');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function drop(Request $request, EmployeeEnrollment $enrollment)
    {
        abort_unless($enrollment->user_id === auth()->id(), 403);

        $this->learningService->dropCourse($enrollment, auth()->id());

        return redirect()->route('staff.learning.index')->with('success', 'Course dropped.');
    }
}
