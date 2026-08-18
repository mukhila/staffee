<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $posting->title }}</h1>
            <span>Job posting detail &amp; applications pipeline</span>
        </div>
        <div class="d-flex gap-2">
            @if($posting->status === 'draft')
            <form method="POST" action="{{ route('admin.recruitment.postings.publish', $posting) }}">
                @csrf
                <button class="btn btn-success btn-sm"><i class="fi fi-rr-globe me-1"></i>Publish</button>
            </form>
            @elseif($posting->status === 'open')
            <form method="POST" action="{{ route('admin.recruitment.postings.close', $posting) }}">
                @csrf
                <button class="btn btn-outline-danger btn-sm">Close Posting</button>
            </form>
            @endif
            <a href="{{ route('admin.recruitment.postings.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        {{-- Posting details --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><strong>Posting Details</strong></div>
                <div class="card-body">
                    @php $statusColors = ['draft'=>'secondary','open'=>'success','closed'=>'danger','on_hold'=>'warning']; @endphp
                    <dl class="row mb-0 small">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            @php $c = $statusColors[$posting->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucwords(str_replace('_',' ',$posting->status)) }}</span>
                        </dd>
                        <dt class="col-5">Department</dt>
                        <dd class="col-7">{{ $posting->department?->name ?? '—' }}</dd>
                        <dt class="col-5">Type</dt>
                        <dd class="col-7">{{ ucwords(str_replace('_',' ',$posting->employment_type)) }}</dd>
                        <dt class="col-5">Location</dt>
                        <dd class="col-7">{{ $posting->location ?? '—' }}</dd>
                        <dt class="col-5">Openings</dt>
                        <dd class="col-7">{{ $posting->openings }}</dd>
                        <dt class="col-5">Salary</dt>
                        <dd class="col-7">
                            @if($posting->salary_min || $posting->salary_max)
                                {{ number_format($posting->salary_min, 0) }} – {{ number_format($posting->salary_max, 0) }}
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-5">Posted By</dt>
                        <dd class="col-7">{{ $posting->postedBy?->name ?? '—' }}</dd>
                        <dt class="col-5">Published</dt>
                        <dd class="col-7">{{ $posting->published_at?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-5">Closes</dt>
                        <dd class="col-7">{{ $posting->closes_at?->format('d M Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><strong>Description</strong></div>
                <div class="card-body small">{!! nl2br(e($posting->description ?? '—')) !!}</div>
            </div>
            <div class="card">
                <div class="card-header"><strong>Requirements</strong></div>
                <div class="card-body small">{!! nl2br(e($posting->requirements ?? '—')) !!}</div>
            </div>
        </div>

        {{-- Application pipeline --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Applications ({{ $posting->applications->count() }})</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Applicant</th>
                                    <th>Email</th>
                                    <th>Source</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $appStatusColors = [
                                    'new'=>'primary','screening'=>'info','interview_scheduled'=>'warning',
                                    'interviewed'=>'warning','offer_sent'=>'success','hired'=>'success',
                                    'rejected'=>'danger','withdrawn'=>'secondary',
                                ];
                                @endphp
                                @forelse($posting->applications as $app)
                                <tr>
                                    <td class="fw-medium">{{ $app->applicant_name }}</td>
                                    <td class="small">{{ $app->applicant_email }}</td>
                                    <td class="small">{{ $app->source ?? '—' }}</td>
                                    <td>
                                        @if($app->rating)
                                            {{ str_repeat('★',$app->rating) }}{{ str_repeat('☆',5-$app->rating) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @php $sc = $appStatusColors[$app->status] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucwords(str_replace('_',' ',$app->status)) }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $app->applied_at->format('d M Y') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.recruitment.applications.show', $app) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No applications yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
