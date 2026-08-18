<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\OnboardingChecklist;
use App\Models\HR\OnboardingTask;
use App\Services\HR\OnboardingService;

class OnboardingController extends Controller
{
    public function __construct(private OnboardingService $service) {}

    public function index()
    {
        $checklists = OnboardingChecklist::with('user')
            ->latest()
            ->paginate(20);

        return view('admin.hr.onboarding.index', compact('checklists'));
    }

    public function show(OnboardingChecklist $checklist)
    {
        $checklist->load(['user', 'tasks.assignedTo', 'tasks.completedBy']);

        return view('admin.hr.onboarding.show', compact('checklist'));
    }

    public function completeTask(OnboardingTask $task)
    {
        $this->service->completeTask($task, auth()->id());

        return back()->with('success', 'Task marked as complete.');
    }
}
