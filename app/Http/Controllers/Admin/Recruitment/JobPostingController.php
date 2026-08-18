<?php

namespace App\Http\Controllers\Admin\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Recruitment\JobPosting;
use Illuminate\Http\Request;

class JobPostingController extends Controller
{
    // ── Admin routes ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = JobPosting::with(['department', 'postedBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $postings = $query->paginate(20)->withQueryString();

        return view('admin.recruitment.postings.index', compact('postings'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.recruitment.postings.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'department_id'   => 'nullable|exists:departments,id',
            'description'     => 'nullable|string',
            'requirements'    => 'nullable|string',
            'employment_type' => 'required|in:full_time,part_time,contract,internship',
            'location'        => 'nullable|string|max:150',
            'salary_min'      => 'nullable|numeric|min:0',
            'salary_max'      => 'nullable|numeric|min:0',
            'openings'        => 'nullable|integer|min:1',
            'status'          => 'required|in:draft,open,closed,on_hold',
            'closes_at'       => 'nullable|date',
        ]);

        $data['posted_by'] = auth()->id();
        if (($data['status'] ?? 'draft') === 'open') {
            $data['published_at'] = now();
        }

        JobPosting::create($data);

        return redirect()->route('admin.recruitment.postings.index')
            ->with('success', 'Job posting created successfully.');
    }

    public function show(JobPosting $posting)
    {
        $posting->load(['department', 'postedBy', 'applications.referredBy']);

        $applicationsByStatus = $posting->applications
            ->groupBy('status');

        return view('admin.recruitment.postings.show', compact('posting', 'applicationsByStatus'));
    }

    public function publish(JobPosting $posting)
    {
        $posting->update([
            'status'       => 'open',
            'published_at' => now(),
        ]);

        return back()->with('success', 'Job posting published and is now accepting applications.');
    }

    public function close(JobPosting $posting)
    {
        $posting->update(['status' => 'closed']);

        return back()->with('success', 'Job posting closed.');
    }

    // ── Public routes ─────────────────────────────────────────────────────────

    public function publicIndex()
    {
        $postings = JobPosting::open()
            ->with('department')
            ->latest('published_at')
            ->paginate(15);

        return view('jobs.index', compact('postings'));
    }

    public function publicShow(JobPosting $posting)
    {
        if ($posting->status !== 'open') {
            abort(404);
        }

        return view('jobs.show', compact('posting'));
    }
}
