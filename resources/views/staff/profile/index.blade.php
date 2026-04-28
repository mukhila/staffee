<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">My Profile</h1>
            <span>View your employment details and documents</span>
        </div>
    </div>

    <div class="row g-3">
        {{-- Left: identity card --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body text-center py-4">
                    <div class="avatar avatar-xl mx-auto mb-3" style="width:72px;height:72px;border-radius:50%;background:#316AFF20;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:600;color:#316AFF;">
                        {{ strtoupper(substr($user->name,0,1)) }}
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $user->name }}</h5>
                    <div class="text-muted small">{{ $user->email }}</div>
                    @if($user->employee_id)
                    <div class="badge bg-primary-subtle text-primary mt-1">{{ $user->employee_id }}</div>
                    @endif
                    <hr class="my-3">
                    <div class="row text-start g-2">
                        <div class="col-6">
                            <div class="text-muted small">Department</div>
                            <div class="fw-medium small">{{ $user->department?->name ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Role</div>
                            <div class="fw-medium small">{{ $user->role?->name ?? ucfirst($user->role) }}</div>
                        </div>
                        @if($profile)
                        <div class="col-6">
                            <div class="text-muted small">Designation</div>
                            <div class="fw-medium small">{{ $profile->designation ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Join Date</div>
                            <div class="fw-medium small">{{ $profile->date_of_joining?->format('d M Y') ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Employment Type</div>
                            <div class="fw-medium small">{{ ucfirst($profile->employment_type ?? '—') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-medium small">{{ $profile->phone ?? '—' }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($profile)
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Personal Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0 small">
                        <tr><th class="text-muted fw-normal ps-0">Date of Birth</th><td>{{ $profile->date_of_birth?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Gender</th><td>{{ ucfirst($profile->gender ?? '—') }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Nationality</th><td>{{ $profile->nationality ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Address</th><td>{{ $profile->address ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Emergency Contact</th><td>{{ $profile->emergency_contact_name ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Emergency Phone</th><td>{{ $profile->emergency_contact_phone ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: tabs --}}
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-3" id="profileTabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-docs">Documents</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-salary">Salary History</a></li>
            </ul>

            <div class="tab-content">
                {{-- Documents --}}
                <div class="tab-pane fade show active" id="tab-docs">
                    <div class="card">
                        <div class="card-body p-0">
                            @if($documents->isEmpty())
                            <div class="text-center text-muted py-5">
                                <i class="fi fi-rr-document fs-3 d-block mb-2 opacity-25"></i>
                                No documents available yet.
                            </div>
                            @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Document</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Verified</th>
                                            <th>Uploaded</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($documents as $doc)
                                        <tr>
                                            <td class="fw-medium">{{ $doc->name }}</td>
                                            <td class="text-muted small">{{ \App\Models\HR\EmployeeDocument::typeLabel($doc->document_type) }}</td>
                                            <td class="text-muted small">{{ $doc->file_size_human }}</td>
                                            <td>
                                                @if($doc->is_verified)
                                                <span class="badge bg-success-subtle text-success"><i class="fi fi-rr-check me-1"></i>Verified</span>
                                                @else
                                                <span class="badge bg-warning-subtle text-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td class="text-muted small">{{ $doc->created_at->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ route('staff.profile.document.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fi fi-rr-download me-1"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Salary History --}}
                <div class="tab-pane fade" id="tab-salary">
                    <div class="card">
                        <div class="card-body p-0">
                            @if($salaryHistory->isEmpty())
                            <div class="text-center text-muted py-5">
                                <i class="fi fi-rr-money fs-3 d-block mb-2 opacity-25"></i>
                                No salary revision history available.
                            </div>
                            @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Effective Date</th>
                                            <th>Previous CTC</th>
                                            <th>New CTC</th>
                                            <th>Change</th>
                                            <th>Type</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($salaryHistory as $rev)
                                        <tr>
                                            <td class="fw-medium">{{ $rev->effective_date->format('d M Y') }}</td>
                                            <td class="text-muted">{{ number_format($rev->old_salary, 2) }}</td>
                                            <td class="fw-bold">{{ number_format($rev->new_salary, 2) }}</td>
                                            <td>
                                                @php $delta = $rev->new_salary - $rev->old_salary; @endphp
                                                <span class="badge bg-{{ $delta >= 0 ? 'success' : 'danger' }}-subtle text-{{ $delta >= 0 ? 'success' : 'danger' }}">
                                                    {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 2) }}
                                                    @if($rev->percentage_change) ({{ $rev->percentage_change }}%) @endif
                                                </span>
                                            </td>
                                            <td class="text-muted small">{{ ucwords(str_replace('_', ' ', $rev->revision_type ?? '')) }}</td>
                                            <td class="text-muted small">{{ Str::limit($rev->reason, 60) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
