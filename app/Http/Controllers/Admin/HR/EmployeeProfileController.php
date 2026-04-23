<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\EmployeeCertification;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\EmployeeEducation;
use App\Models\HR\EmployeeEmergencyContact;
use App\Models\HR\EmployeeExperience;
use App\Models\HR\EmployeeProfile;
use App\Models\HR\EmployeeSkill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeProfileController extends Controller
{
    // ─── Profile ──────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->authorize('view-staff');

        $employees = User::excludeAdmin()
            ->withHrProfile()
            ->with(['department', 'profile'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('employee_id', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->when($request->status, fn ($q) => $q->where('employment_status', $request->status))
            ->when($request->department, fn ($q) => $q->where('department_id', $request->department))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments = \App\Models\Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.hr.employees.index', compact('employees', 'departments'));
    }

    public function show(User $employee)
    {
        $this->authorize('view-staff');

        $employee->load([
            'profile.education', 'profile.experience', 'profile.skills',
            'profile.certifications', 'profile.documents', 'profile.emergencyContacts',
            'department', 'manager',
            'lifecycleEvents' => fn ($q) => $q->visible()->latest('effective_date')->take(10),
            'salaryRevisions'  => fn ($q) => $q->latest('effective_date')->take(5),
        ]);

        $profile = $employee->profile ?? new EmployeeProfile(['user_id' => $employee->id]);

        return view('admin.hr.profile.show', compact('employee', 'profile'));
    }

    public function editProfile(User $employee)
    {
        $this->authorize('edit-staff');

        $profile      = $employee->profile ?? new EmployeeProfile(['user_id' => $employee->id]);
        $departments  = \App\Models\Department::where('is_active', true)->get();
        $managers     = User::active()->excludeAdmin()->where('id', '!=', $employee->id)->get();

        return view('admin.hr.profile.edit', compact('employee', 'profile', 'departments', 'managers'));
    }

    public function updateProfile(Request $request, User $employee)
    {
        $this->authorize('edit-staff');

        $validated = $request->validate([
            'designation'        => 'nullable|string|max:255',
            'date_of_birth'      => 'nullable|date|before:today',
            'gender'             => 'nullable|in:male,female,other,prefer_not_to_say',
            'blood_group'        => 'nullable|string|max:5',
            'marital_status'     => 'nullable|in:single,married,divorced,widowed',
            'nationality'        => 'nullable|string|max:100',
            'national_id'        => 'nullable|string|max:100',
            'national_id_type'   => 'nullable|string|max:50',
            'joining_date'       => 'nullable|date',
            'probation_end_date' => 'nullable|date|after_or_equal:joining_date',
            'contract_type'      => 'nullable|in:permanent,fixed_term,internship,part_time,consultant',
            'contract_end_date'  => 'nullable|date|after_or_equal:joining_date',
            'notice_period_days' => 'nullable|integer|min:0|max:365',
            'work_location'      => 'nullable|string|max:100',
            'current_salary'     => 'nullable|numeric|min:0',
            'salary_currency'    => 'nullable|string|size:3',
            'linkedin_url'       => 'nullable|url|max:255',
            'github_url'         => 'nullable|url|max:255',
            'bio'                => 'nullable|string|max:1000',
            'perm_address_line1' => 'nullable|string|max:255',
            'perm_city'          => 'nullable|string|max:100',
            'perm_state'         => 'nullable|string|max:100',
            'perm_postal_code'   => 'nullable|string|max:20',
            'perm_country'       => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($employee, $request, $validated) {
            // User-level fields
            $employee->update(array_filter([
                'designation' => $validated['designation'] ?? null,
                'phone'       => $request->phone,
                'address'     => $request->address,
            ]));

            // Profile upsert
            EmployeeProfile::updateOrCreate(
                ['user_id' => $employee->id],
                $validated
            );
        });

        return redirect()->route('admin.hr.employees.show', $employee)
                         ->with('success', 'Profile updated successfully.');
    }

    // ─── Education ────────────────────────────────────────────────────────────

    public function storeEducation(Request $request, User $employee)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'institution_name' => 'required|string|max:255',
            'degree'           => 'required|string|max:255',
            'field_of_study'   => 'nullable|string|max:255',
            'start_year'       => 'nullable|integer|min:1950|max:' . date('Y'),
            'end_year'         => 'nullable|integer|min:1950|max:' . (date('Y') + 5),
            'grade_gpa'        => 'nullable|string|max:50',
        ]);

        EmployeeEducation::create(array_merge(
            $request->only(['institution_name', 'degree', 'field_of_study', 'start_year', 'end_year', 'grade_gpa']),
            ['user_id' => $employee->id, 'is_current' => $request->boolean('is_current')]
        ));

        return back()->with('success', 'Education record added.');
    }

    public function destroyEducation(User $employee, EmployeeEducation $education)
    {
        $this->authorize('edit-staff');
        abort_if($education->user_id !== $employee->id, 403);
        $education->delete();
        return back()->with('success', 'Education record removed.');
    }

    // ─── Experience ───────────────────────────────────────────────────────────

    public function storeExperience(Request $request, User $employee)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'company_name'    => 'required|string|max:255',
            'position'        => 'required|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,contract,freelance,internship',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
        ]);

        EmployeeExperience::create(array_merge(
            $request->only(['company_name', 'position', 'department', 'location', 'employment_type', 'start_date', 'end_date', 'description']),
            ['user_id' => $employee->id, 'is_current' => $request->boolean('is_current')]
        ));

        return back()->with('success', 'Experience record added.');
    }

    public function destroyExperience(User $employee, EmployeeExperience $experience)
    {
        $this->authorize('edit-staff');
        abort_if($experience->user_id !== $employee->id, 403);
        $experience->delete();
        return back()->with('success', 'Experience record removed.');
    }

    // ─── Skills ───────────────────────────────────────────────────────────────

    public function storeSkill(Request $request, User $employee)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'nullable|string|max:100',
            'proficiency' => 'required|in:beginner,intermediate,advanced,expert',
        ]);

        EmployeeSkill::create(array_merge(
            $request->only(['name', 'category', 'proficiency', 'years_of_experience']),
            ['user_id' => $employee->id]
        ));

        return back()->with('success', 'Skill added.');
    }

    public function destroySkill(User $employee, EmployeeSkill $skill)
    {
        $this->authorize('edit-staff');
        abort_if($skill->user_id !== $employee->id, 403);
        $skill->delete();
        return back()->with('success', 'Skill removed.');
    }

    // ─── Documents ────────────────────────────────────────────────────────────

    public function storeDocument(Request $request, User $employee)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'document_type' => 'required|in:resume,id_proof,offer_letter,contract,increment_letter,relieving_letter,experience_letter,nda,appraisal,other',
            'name'          => 'required|string|max:255',
            'file'          => 'required|file|max:5120|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        $file = $request->file('file');
        $path = $file->store("employees/{$employee->id}/documents", 'public');

        EmployeeDocument::create([
            'user_id'       => $employee->id,
            'document_type' => $request->document_type,
            'name'          => $request->name,
            'file_path'     => $path,
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'uploaded_by'   => auth()->id(),
            'notes'         => $request->notes,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function destroyDocument(User $employee, EmployeeDocument $document)
    {
        $this->authorize('edit-staff');
        abort_if($document->user_id !== $employee->id, 403);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return back()->with('success', 'Document deleted.');
    }

    // ─── Emergency contacts ───────────────────────────────────────────────────

    public function storeEmergencyContact(Request $request, User $employee)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'name'         => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'phone'        => 'required|string|max:20',
        ]);

        EmployeeEmergencyContact::create(array_merge(
            $request->only(['name', 'relationship', 'phone', 'alt_phone', 'email', 'address']),
            ['user_id' => $employee->id, 'is_primary' => $request->boolean('is_primary')]
        ));

        return back()->with('success', 'Emergency contact added.');
    }

    public function destroyEmergencyContact(User $employee, EmployeeEmergencyContact $contact)
    {
        $this->authorize('edit-staff');
        abort_if($contact->user_id !== $employee->id, 403);
        $contact->delete();
        return back()->with('success', 'Contact removed.');
    }
}
