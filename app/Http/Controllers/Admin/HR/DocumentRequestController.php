<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentRequest::with('user')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20)->withQueryString();
        $statuses = ['pending', 'processing', 'ready', 'rejected'];

        return view('admin.hr.document-requests.index', compact('requests', 'statuses'));
    }

    public function show(DocumentRequest $documentRequest)
    {
        $documentRequest->load('user');

        return view('admin.hr.document-requests.show', [
            'dr' => $documentRequest,
        ]);
    }

    public function fulfill(Request $request, DocumentRequest $documentRequest)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'notes'    => 'nullable|string|max:1000',
        ]);

        $path = $request->file('document')->store('document-requests', 'public');

        $documentRequest->update([
            'status'        => 'ready',
            'document_path' => $path,
            'admin_notes'   => $request->notes,
            'fulfilled_at'  => now(),
        ]);

        return redirect()->route('admin.document-requests.show', $documentRequest)
            ->with('success', 'Document uploaded and request marked as ready.');
    }

    public function reject(Request $request, DocumentRequest $documentRequest)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $documentRequest->update([
            'status'      => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        return redirect()->route('admin.document-requests.show', $documentRequest)
            ->with('success', 'Document request rejected.');
    }
}
