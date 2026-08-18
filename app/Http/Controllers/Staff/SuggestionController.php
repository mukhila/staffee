<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\HR\Suggestion;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function index()
    {
        $suggestions = Suggestion::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('staff.suggestions.index', compact('suggestions'));
    }

    public function create()
    {
        $categories = ['Culture', 'Process', 'Benefits', 'Tools', 'Communication', 'Other'];

        return view('staff.suggestions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:200',
            'body'         => 'required|string',
            'category'     => 'nullable|string|max:80',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $isAnonymous = (bool) ($validated['is_anonymous'] ?? false);

        Suggestion::create([
            'user_id'      => $isAnonymous ? null : auth()->id(),
            'is_anonymous' => $isAnonymous,
            'title'        => $validated['title'],
            'body'         => $validated['body'],
            'category'     => $validated['category'] ?? null,
            'status'       => 'new',
        ]);

        return redirect()->route('staff.suggestions.index')
            ->with('success', 'Your suggestion has been submitted. Thank you!');
    }
}
