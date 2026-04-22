<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->orderBy('created_at', 'desc')->get();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'body'     => 'required|string',
            'audience' => 'required|in:all,staff,pm',
        ]);

        Announcement::create([
            'created_by' => auth()->id(),
            'title'      => $request->title,
            'body'       => $request->body,
            'audience'   => $request->audience,
            'is_active'  => true,
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement published.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title'     => 'required|string|max:255',
            'body'      => 'required|string',
            'audience'  => 'required|in:all,staff,pm',
            'is_active' => 'boolean',
        ]);

        $announcement->update([
            'title'     => $request->title,
            'body'      => $request->body,
            'audience'  => $request->audience,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }
}
