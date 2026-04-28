<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\EmployeeProfile;
use App\Models\HR\SalaryRevision;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        $user    = auth()->user()->load('department');
        $profile = EmployeeProfile::where('user_id', $user->id)->first();

        $documents = EmployeeDocument::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $salaryHistory = SalaryRevision::where('user_id', $user->id)
            ->latestFirst()
            ->get();

        return view('staff.profile.index', compact('user', 'profile', 'documents', 'salaryHistory'));
    }

    public function downloadDocument(EmployeeDocument $document)
    {
        abort_if($document->user_id !== auth()->id(), 403);
        abort_if(!Storage::exists($document->file_path), 404);

        return Storage::download($document->file_path, $document->name . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION));
    }
}
