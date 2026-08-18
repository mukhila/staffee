<?php

namespace App\Http\Controllers\Admin\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Recruitment\JobApplication;
use App\Models\Recruitment\JobPosting;
use App\Services\Recruitment\JobApplicationService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class JobApplicationController extends Controller
{
    public function __construct(private JobApplicationService $service) {}

    // ── Admin routes ──────────────────────────────────────────────────────────

    public function show(JobApplication $application)
    {
        $application->load(['jobPosting', 'referredBy']);

        return view('admin.recruitment.applications.show', compact('application'));
    }

    public function updateStatus(Request $request, JobApplication $application)
    {
        $data = $request->validate([
            'status'   => 'required|string',
            'hr_notes' => 'nullable|string',
            'rating'   => 'nullable|integer|min:1|max:5',
        ]);

        if (!empty($data['hr_notes']) || isset($data['rating'])) {
            $application->update(array_filter([
                'hr_notes' => $data['hr_notes'] ?? null,
                'rating'   => $data['rating'] ?? null,
            ], fn($v) => $v !== null));
        }

        try {
            $this->service->advanceStatus($application, $data['status'], auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Application status updated.');
    }

    public function hire(JobApplication $application)
    {
        try {
            $checklist = $this->service->hireApplicant($application, auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['hire' => $e->getMessage()]);
        }

        return redirect()->route('admin.onboarding.show', $checklist)
            ->with('success', 'Applicant hired. Onboarding checklist created.');
    }

    // ── Public route ──────────────────────────────────────────────────────────

    public function apply(Request $request, JobPosting $posting)
    {
        $data = $request->validate([
            'applicant_name'  => 'required|string|max:150',
            'applicant_email' => 'required|email|max:180',
            'applicant_phone' => 'nullable|string|max:30',
            'resume'          => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'cover_letter'    => 'nullable|string',
            'source'          => 'nullable|string|max:80',
        ]);

        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume');
        }

        try {
            $this->service->applyToJob($posting, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['posting' => $e->getMessage()])->withInput();
        }

        return redirect()->route('jobs.index')
            ->with('success', 'Your application has been submitted successfully!');
    }
}
