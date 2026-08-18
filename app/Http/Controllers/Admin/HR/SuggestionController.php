<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Suggestion;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Suggestion::with(['user', 'respondedBy'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $suggestions = $query->paginate(20)->withQueryString();
        $statuses    = Suggestion::STATUS_LABELS;

        return view('admin.hr.suggestions.index', compact('suggestions', 'statuses'));
    }

    public function show(Suggestion $suggestion)
    {
        $suggestion->load(['user', 'respondedBy']);

        return view('admin.hr.suggestions.show', compact('suggestion'));
    }

    public function respond(Request $request, Suggestion $suggestion)
    {
        $validated = $request->validate([
            'status'         => 'required|in:new,under_review,implemented,rejected',
            'admin_response' => 'required|string|max:2000',
        ]);

        $suggestion->update([
            'status'         => $validated['status'],
            'admin_response' => $validated['admin_response'],
            'responded_by'   => auth()->id(),
        ]);

        return redirect()->route('admin.suggestions.show', $suggestion)
            ->with('success', 'Response saved successfully.');
    }
}
