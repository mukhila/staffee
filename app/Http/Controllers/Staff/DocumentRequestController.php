<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\HR\DocumentRequest;
use Illuminate\Http\Request;

class DocumentRequestController extends Controller
{
    public function index()
    {
        $requests = DocumentRequest::forUser(auth()->id())
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('staff.document-requests.index', compact('requests'));
    }

    public function create()
    {
        $types = DocumentRequest::TYPE_LABELS;

        return view('staff.document-requests.create', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:experience_letter,salary_certificate,noc,appointment_letter,promotion_letter,custom',
            'custom_type'   => 'nullable|required_if:document_type,custom|string|max:120',
            'purpose'       => 'nullable|string|max:1000',
        ]);

        DocumentRequest::create([
            'user_id'       => auth()->id(),
            'document_type' => $validated['document_type'],
            'custom_type'   => $validated['custom_type'] ?? null,
            'purpose'       => $validated['purpose'] ?? null,
            'status'        => 'pending',
            'requested_at'  => now(),
        ]);

        return redirect()->route('staff.document-requests.index')
            ->with('success', 'Document request submitted successfully.');
    }
}
