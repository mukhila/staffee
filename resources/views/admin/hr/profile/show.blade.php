<x-app-layout>
<div class="container-fluid">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            @if($employee->avatar)
                <img src="{{ asset('storage/'.$employee->avatar) }}" class="rounded-circle" width="52" height="52">
            @else
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width:52px;height:52px">
                    {{ strtoupper(substr($employee->name,0,2)) }}
                </div>
            @endif
            <div>
                <h1 class="app-page-title mb-0">{{ $employee->name }}</h1>
                <span class="text-muted">
                    {{ $employee->designation ?? ucfirst($employee->role) }}
                    @if($employee->department) · {{ $employee->department->name }} @endif
                    @if($employee->employee_id) · <code>{{ $employee->employee_id }}</code> @endif
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @php
                $statusColors = ['active'=>'success','probation'=>'warning','notice_period'=>'danger','suspended'=>'danger','terminated'=>'dark','resigned'=>'secondary'];
                $sc = $statusColors[$employee->employment_status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $sc }} fs-6">{{ ucwords(str_replace('_',' ',$employee->employment_status)) }}</span>
            <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-outline-primary btn-sm">
                <i class="fi fi-rr-edit me-1"></i> Edit Profile
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">

        {{-- ── Left column: personal info + employment ─────────────────────── --}}
        <div class="col-lg-4">

            {{-- Personal --}}
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Personal Information</h6></div>
                <div class="card-body">
                    @php $p = $profile; @endphp
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal w-50">Date of Birth</th><td>{{ $p->date_of_birth?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Age</th><td>{{ $p->age ? $p->age.' yrs' : '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Gender</th><td>{{ ucfirst($p->gender ?? '—') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Blood Group</th><td>{{ $p->blood_group ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Marital Status</th><td>{{ ucfirst($p->marital_status ?? '—') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Nationality</th><td>{{ $p->nationality ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Phone</th><td>{{ $employee->phone ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Employment --}}
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Employment</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal w-50">Joining Date</th><td>{{ $p->joining_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Service</th><td>{{ $p->years_of_service ? $p->years_of_service.' yrs' : '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Contract</th><td>{{ ucwords(str_replace('_',' ',$p->contract_type ?? '—')) }}</td></tr>
                        @if($p->contract_end_date)
                        <tr>
                            <th class="text-muted fw-normal">Contract End</th>
                            <td class="{{ $p->isContractExpiring() ? 'text-danger fw-semibold' : '' }}">
                                {{ $p->contract_end_date->format('d M Y') }}
                                @if($p->isContractExpiring()) <span class="badge bg-danger ms-1">Expiring</span> @endif
                            </td>
                        </tr>
                        @endif
                        <tr><th class="text-muted fw-normal">Notice Period</th><td>{{ $p->notice_period_days ?? 30 }} days</td></tr>
                        <tr><th class="text-muted fw-normal">Work Location</th><td>{{ ucfirst($p->work_location ?? '—') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Manager</th><td>{{ $employee->manager?->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Emergency contacts --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Emergency Contacts</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalContact">+ Add</button>
                </div>
                <div class="card-body p-0">
                    @forelse($profile->emergencyContacts as $c)
                    <div class="d-flex justify-content-between align-items-start px-3 py-2 border-bottom">
                        <div>
                            <div class="fw-medium">{{ $c->name }} <small class="text-muted">({{ $c->relationship }})</small></div>
                            <div class="small text-muted">{{ $c->phone }}</div>
                        </div>
                        <form action="{{ route('admin.hr.employees.contacts.destroy', [$employee, $c]) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Remove?')"><i class="fi fi-rr-trash"></i></button>
                        </form>
                    </div>
                    @empty
                    <p class="text-muted small text-center py-3 mb-0">No emergency contacts added.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- ── Right column: tabs ───────────────────────────────────────────── --}}
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" id="profileTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-experience">Experience</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-education">Education</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-skills">Skills</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents">Documents</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-timeline">Timeline</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-salary">Salary</button></li>
            </ul>

            <div class="tab-content">

                {{-- Experience --}}
                <div class="tab-pane fade show active" id="tab-experience">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                            <h6 class="card-title mb-0">Work Experience</h6>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExperience">+ Add</button>
                        </div>
                        <div class="card-body">
                            @forelse($profile->experience as $exp)
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <div>
                                    <div class="fw-semibold">{{ $exp->position }}</div>
                                    <div class="text-muted">{{ $exp->company_name }} @if($exp->location) · {{ $exp->location }} @endif</div>
                                    <div class="small text-muted">
                                        {{ $exp->start_date->format('M Y') }} –
                                        {{ $exp->is_current ? 'Present' : $exp->end_date?->format('M Y') }}
                                        · {{ $exp->duration_label }}
                                    </div>
                                    @if($exp->description)<p class="small mt-1 mb-0">{{ $exp->description }}</p>@endif
                                </div>
                                <form action="{{ route('admin.hr.employees.experience.destroy', [$employee, $exp]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger"><i class="fi fi-rr-trash"></i></button>
                                </form>
                            </div>
                            @empty <p class="text-muted">No experience records.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Education --}}
                <div class="tab-pane fade" id="tab-education">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                            <h6 class="card-title mb-0">Education</h6>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEducation">+ Add</button>
                        </div>
                        <div class="card-body">
                            @forelse($profile->education as $edu)
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <div>
                                    <div class="fw-semibold">{{ $edu->degree }} @if($edu->field_of_study) in {{ $edu->field_of_study }} @endif</div>
                                    <div class="text-muted">{{ $edu->institution_name }}</div>
                                    <div class="small text-muted">{{ $edu->duration }} @if($edu->grade_gpa) · {{ $edu->grade_gpa }} @endif</div>
                                </div>
                                <form action="{{ route('admin.hr.employees.education.destroy', [$employee, $edu]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger"><i class="fi fi-rr-trash"></i></button>
                                </form>
                            </div>
                            @empty <p class="text-muted">No education records.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Skills --}}
                <div class="tab-pane fade" id="tab-skills">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                            <h6 class="card-title mb-0">Skills</h6>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSkill">+ Add</button>
                        </div>
                        <div class="card-body">
                            @forelse($profile->skills->groupBy('category') as $category => $skills)
                            <div class="mb-3">
                                <div class="text-muted small fw-semibold mb-2">{{ $category ?: 'General' }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($skills as $skill)
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-{{ $skill->proficiency_color }}-subtle text-{{ $skill->proficiency_color }} px-3 py-2">
                                            {{ $skill->name }}
                                            <small class="ms-1 opacity-75">{{ ucfirst($skill->proficiency) }}</small>
                                        </span>
                                        <form action="{{ route('admin.hr.employees.skills.destroy', [$employee, $skill]) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-link btn-sm p-0 text-muted"><i class="fi fi-rr-cross-small"></i></button>
                                        </form>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @empty <p class="text-muted">No skills added.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Documents --}}
                <div class="tab-pane fade" id="tab-documents">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                            <h6 class="card-title mb-0">Documents</h6>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDocument">+ Upload</button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light"><tr><th>Name</th><th>Type</th><th>Size</th><th>Verified</th><th></th></tr></thead>
                                <tbody>
                                @forelse($profile->documents as $doc)
                                <tr>
                                    <td>{{ $doc->name }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ \App\Models\HR\EmployeeDocument::typeLabel($doc->document_type) }}</span></td>
                                    <td class="text-muted small">{{ $doc->file_size_human }}</td>
                                    <td>
                                        @if($doc->is_verified)
                                            <span class="badge bg-success-subtle text-success">Verified</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fi fi-rr-download"></i>
                                        </a>
                                        <form action="{{ route('admin.hr.employees.documents.destroy', [$employee, $doc]) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete document?')"><i class="fi fi-rr-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No documents uploaded.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="tab-pane fade" id="tab-timeline">
                    <div class="card">
                        <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Lifecycle Timeline</h6></div>
                        <div class="card-body">
                            @php
                                $eventIcons = [
                                    'joined'=>'fi-rr-enter','promotion'=>'fi-rr-arrow-up','demotion'=>'fi-rr-arrow-down',
                                    'transfer'=>'fi-rr-arrows-repeat','warning'=>'fi-rr-triangle-warning',
                                    'resignation_accepted'=>'fi-rr-sign-out-alt','termination'=>'fi-rr-user-minus',
                                    'salary_revision'=>'fi-rr-money-bill','probation_completed'=>'fi-rr-check',
                                ];
                                $eventColors = [
                                    'joined'=>'success','promotion'=>'primary','demotion'=>'warning','transfer'=>'info',
                                    'warning'=>'danger','resignation_accepted'=>'secondary','termination'=>'dark',
                                    'salary_revision'=>'info','probation_completed'=>'success',
                                ];
                            @endphp
                            @forelse($employee->lifecycleEvents as $event)
                            @php
                                $icon  = $eventIcons[$event->event_type]  ?? 'fi-rr-clock';
                                $color = $eventColors[$event->event_type] ?? 'secondary';
                            @endphp
                            <div class="d-flex gap-3 mb-4">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-{{ $color }}-subtle text-{{ $color }} d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                                        <i class="fi {{ $icon }}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 border-bottom pb-3">
                                    <div class="d-flex justify-content-between">
                                        <div class="fw-semibold">{{ $event->title }}</div>
                                        <div class="text-muted small">{{ $event->effective_date->format('d M Y') }}</div>
                                    </div>
                                    @if($event->description)<p class="text-muted small mb-1">{{ $event->description }}</p>@endif
                                    <div class="small text-muted">By {{ $event->performer->name }}</div>
                                </div>
                            </div>
                            @empty
                            <p class="text-muted text-center">No lifecycle events recorded.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Salary history --}}
                <div class="tab-pane fade" id="tab-salary">
                    <div class="card">
                        <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Salary History</h6></div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light"><tr><th>Effective Date</th><th>Old Salary</th><th>New Salary</th><th>Change</th><th>Type</th></tr></thead>
                                <tbody>
                                @forelse($employee->salaryRevisions as $rev)
                                <tr>
                                    <td>{{ $rev->effective_date->format('d M Y') }}</td>
                                    <td>{{ $rev->old_salary ? number_format($rev->old_salary) : '—' }}</td>
                                    <td class="fw-semibold">{{ number_format($rev->new_salary) }}</td>
                                    <td>
                                        @if($rev->percentage_change)
                                            <span class="badge bg-{{ $rev->percentage_change >= 0 ? 'success' : 'danger' }}-subtle text-{{ $rev->percentage_change >= 0 ? 'success' : 'danger' }}">
                                                {{ $rev->percentage_change >= 0 ? '+' : '' }}{{ $rev->percentage_change }}%
                                            </span>
                                        @else —
                                        @endif
                                    </td>
                                    <td><span class="badge bg-info-subtle text-info">{{ ucwords(str_replace('_',' ',$rev->revision_type)) }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No salary history.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>{{-- tab-content --}}
        </div>{{-- col --}}
    </div>{{-- row --}}

    {{-- ── Modals ──────────────────────────────────────────────────────────── --}}

    {{-- Add Experience --}}
    <div class="modal fade" id="modalExperience" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('admin.hr.employees.experience.store', $employee) }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add Experience</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label">Company *</label><input name="company_name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Position *</label><input name="position" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Type *</label>
                        <select name="employment_type" class="form-select" required>
                            @foreach(['full_time','part_time','contract','freelance','internship'] as $t)
                            <option value="{{ $t }}">{{ ucwords(str_replace('_',' ',$t)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Location</label><input name="location" class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">Start Date *</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-md-5"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control"></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_current" id="expCurrent"><label class="form-check-label" for="expCurrent">Current</label></div></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div></div>
    </div>

    {{-- Add Education --}}
    <div class="modal fade" id="modalEducation" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('admin.hr.employees.education.store', $employee) }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add Education</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Institution *</label><input name="institution_name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Degree *</label><input name="degree" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Field of Study</label><input name="field_of_study" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Start Year</label><input type="number" name="start_year" class="form-control" min="1950" max="{{ date('Y') }}"></div>
                    <div class="col-md-4"><label class="form-label">End Year</label><input type="number" name="end_year" class="form-control" min="1950" max="{{ date('Y') + 5 }}"></div>
                    <div class="col-md-4"><label class="form-label">Grade/GPA</label><input name="grade_gpa" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div></div>
    </div>

    {{-- Add Skill --}}
    <div class="modal fade" id="modalSkill" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('admin.hr.employees.skills.store', $employee) }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add Skill</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-7"><label class="form-label">Skill Name *</label><input name="name" class="form-control" required></div>
                    <div class="col-md-5"><label class="form-label">Category</label><input name="category" class="form-control" placeholder="e.g. Language"></div>
                    <div class="col-md-6"><label class="form-label">Proficiency *</label>
                        <select name="proficiency" class="form-select" required>
                            @foreach(['beginner','intermediate','advanced','expert'] as $lv)
                            <option value="{{ $lv }}">{{ ucfirst($lv) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Years of Experience</label><input type="number" name="years_of_experience" class="form-control" min="0" max="50"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div></div>
    </div>

    {{-- Upload Document --}}
    <div class="modal fade" id="modalDocument" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('admin.hr.employees.documents.store', $employee) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Upload Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label">Document Type *</label>
                        <select name="document_type" class="form-select" required>
                            @foreach(['resume','id_proof','offer_letter','contract','increment_letter','relieving_letter','experience_letter','nda','appraisal','other'] as $t)
                            <option value="{{ $t }}">{{ \App\Models\HR\EmployeeDocument::typeLabel($t) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Display Name *</label><input name="name" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">File * <small class="text-muted">(PDF, DOC, JPG, PNG — max 5 MB)</small></label><input type="file" name="file" class="form-control" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Upload</button></div>
            </form>
        </div></div>
    </div>

    {{-- Add Emergency Contact --}}
    <div class="modal fade" id="modalContact" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('admin.hr.employees.contacts.store', $employee) }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add Emergency Contact</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Relationship *</label><input name="relationship" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Phone *</label><input name="phone" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-12 d-flex align-items-center gap-2"><input class="form-check-input" type="checkbox" name="is_primary" value="1"><label>Set as primary</label></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div></div>
    </div>

</div>
</x-app-layout>
