<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = \App\Models\Project::with('users')->get();
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        $users = \App\Models\User::where('role', '!=', 'admin')->get();
        return view('admin.projects.create', compact('users'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimation_time' => 'nullable|string',
            'team_members' => 'array',
            'team_members.*' => 'exists:users,id',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $documents = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('projects', 'public');
                $documents[] = $path;
            }
        }

        $project = \App\Models\Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimation_time' => $request->estimation_time,
            'status' => 'pending',
            'created_by' => auth()->id(),
            'documents' => $documents,
        ]);

        if ($request->has('team_members')) {
            $project->users()->attach($request->team_members);
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(\App\Models\Project $project)
    {
        $users = \App\Models\User::where('role', '!=', 'admin')->get();
        return view('admin.projects.edit', compact('project', 'users'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimation_time' => 'nullable|string',
            'team_members' => 'array',
            'team_members.*' => 'exists:users,id',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $documents = $project->documents ?? [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('projects', 'public');
                $documents[] = $path;
            }
        }

        $project->update([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimation_time' => $request->estimation_time,
            'documents' => $documents,
        ]);

        if ($request->has('team_members')) {
            $project->users()->sync($request->team_members);
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(\App\Models\Project $project)
    {
        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully.');
    }
}
