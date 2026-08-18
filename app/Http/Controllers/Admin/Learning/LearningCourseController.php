<?php

namespace App\Http\Controllers\Admin\Learning;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningCourse;
use App\Models\User;
use App\Services\Learning\LearningService;
use Illuminate\Http\Request;

class LearningCourseController extends Controller
{
    public function __construct(private LearningService $learningService) {}

    public function index()
    {
        $courses = LearningCourse::withCount('enrollments')->latest()->paginate(20);

        return view('admin.learning.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.learning.courses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:200',
            'description'    => 'nullable|string',
            'provider'       => 'nullable|string|max:150',
            'category'       => 'nullable|string|max:80',
            'duration_hours' => 'nullable|numeric|min:0',
            'cost'           => 'nullable|numeric|min:0',
            'is_mandatory'   => 'boolean',
            'status'         => 'required|in:draft,active,archived',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_mandatory'] = $request->boolean('is_mandatory');

        LearningCourse::create($validated);

        return redirect()->route('admin.learning.courses.index')->with('success', 'Course created successfully.');
    }

    public function show(LearningCourse $course)
    {
        $course->load(['enrollments.user', 'createdBy']);
        $users = User::orderBy('name')->get();

        return view('admin.learning.courses.show', compact('course', 'users'));
    }

    public function enroll(Request $request, LearningCourse $course)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        try {
            $this->learningService->enroll($course, $user, auth()->id());
            return redirect()->route('admin.learning.courses.show', $course)
                ->with('success', "{$user->name} enrolled successfully.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
