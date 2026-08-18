<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Application: {{ $application->applicant_name }}</h1>
            <span>{{ $application->jobPosting?->title }}</span>
        </div>
        <a href="{{ route('admin.recruitment.postings.show', $application->job_posting_id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i>Back to Posting
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        {{-- Applicant card --}}
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header"><strong>Applicant Info</strong></div>
                <div class="card-body small">
                    @php
                    $statusColors = [
                        'new'=>'primary','screening'=>'info','interview_scheduled'=>'warning',
                        'interviewed'=>'warning','offer_sent'=>'success','hired'=>'success',
                        'rejected'=>'danger','withdrawn'=>'secondary',
                    ];
                    $sc = $statusColors[$application->status] ?? 'secondary';
                    @endphp
                    <dl class="row mb-0">
                        <dt class="col-5">Name</dt>
                        <dd class="col-7">{{ $application->applicant_name }}</dd>
                        <dt class="col-5">Email</dt>
                        <dd class="col-7"><a href="mailto:{{ $application->applicant_email }}">{{ $application->applicant_email }}</a></dd>
                        <dt class="col-5">Phone</dt>
                        <dd class="col-7">{{ $application->applicant_phone ?? '—' }}</dd>
                        <dt class="col-5">Source</dt>
                        <dd class="col-7">{{ $application->source ?? '—' }}</dd>
                        <dt class="col-5">Referred By</dt>
                        <dd class="col-7">{{ $application->referredBy?->name ?? '—' }}</dd>
                        <dt class="col-5">Applied</dt>
                        <dd class="col-7">{{ $application->applied_at->format('d M Y') }}</dd>
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucwords(str_replace('_',' ',$application->status)) }}</span>
                        </dd>
                        <dt class="col-5">Rating</dt>
                        <dd class="col-7">
                            @if($application->rating)
                                {{ str_repeat('★',$application->rating) }}{{ str_repeat('☆',5-$application->rating) }}
                            @else
                                —
                            @endif
                        </dd>
                        @if($application->resume_path)
                        <dt class="col-5">Resume</dt>
                        <dd class="col-7"><span class="badge bg-secondary">On File</span></dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Advance status --}}
            @php
            $transitions = [
                'new'=>['screening','rejected','withdrawn'],
                'screening'=>['interview_scheduled','rejected','withdrawn'],
                'interview_scheduled'=>['interviewed','rejected','withdrawn'],
                'interviewed'=>['offer_sent','rejected','withdrawn'],
                'offer_sent'=>['hired','rejected','withdrawn'],
            ];
            $next = $transitions[$application->status] ?? [];
            @endphp

            @if(count($next))
            <div class="card mb-3">
                <div class="card-header"><strong>Advance Pipeline</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.recruitment.applications.status', $application) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label form-label-sm">New Status</label>
                            <select name="status" class="form-select form-select-sm">
                                @foreach($next as $s)
                                <option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm">Rating (1–5)</label>
                            <input type="number" name="rating" class="form-control form-control-sm" min="1" max="5" value="{{ $application->rating }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm">HR Notes</label>
                            <textarea name="hr_notes" class="form-control form-control-sm" rows="3">{{ $application->hr_notes }}</textarea>
                        </div>
                        <button class="btn btn-primary btn-sm w-100">Update Status</button>
                    </form>
                </div>
            </div>
            @endif

            @if(in_array($application->status, ['offer_sent','interviewed']))
            <div class="card">
                <div class="card-body text-center">
                    <form method="POST" action="{{ route('admin.recruitment.applications.hire', $application) }}">
                        @csrf
                        <button class="btn btn-success btn-sm w-100">
                            <i class="fi fi-rr-user-check me-1"></i>Hire & Start Onboarding
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Cover letter & notes --}}
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><strong>Cover Letter</strong></div>
                <div class="card-body small">{!! nl2br(e($application->cover_letter ?? '—')) !!}</div>
            </div>
            <div class="card">
                <div class="card-header"><strong>HR Notes</strong></div>
                <div class="card-body small">{!! nl2br(e($application->hr_notes ?? '—')) !!}</div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
