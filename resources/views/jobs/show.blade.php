<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $posting->title }} — Staffee Careers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-4">
    <a class="navbar-brand fw-bold" href="{{ route('jobs.index') }}">Staffee Careers</a>
</nav>

<div class="container py-5">
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="row g-4">
        {{-- Job details --}}
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h4 class="fw-bold">{{ $posting->title }}</h4>
                    <div class="text-muted small mb-3">
                        @if($posting->department) <div>{{ $posting->department->name }}</div> @endif
                        @if($posting->location) <div>{{ $posting->location }}</div> @endif
                        <div>{{ ucwords(str_replace('_',' ',$posting->employment_type)) }}</div>
                        @if($posting->salary_min || $posting->salary_max)
                        <div>{{ number_format($posting->salary_min, 0) }} – {{ number_format($posting->salary_max, 0) }}</div>
                        @endif
                        @if($posting->closes_at)
                        <div>Closes: {{ $posting->closes_at->format('d M Y') }}</div>
                        @endif
                    </div>

                    @if($posting->description)
                    <h6 class="fw-semibold">About This Role</h6>
                    <p class="small">{!! nl2br(e($posting->description)) !!}</p>
                    @endif

                    @if($posting->requirements)
                    <h6 class="fw-semibold">Requirements</h6>
                    <p class="small">{!! nl2br(e($posting->requirements)) !!}</p>
                    @endif
                </div>
            </div>
            <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary btn-sm">← All Openings</a>
        </div>

        {{-- Apply form --}}
        <div class="col-md-7 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Apply for this Position</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('jobs.apply', $posting) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="applicant_name" class="form-control @error('applicant_name') is-invalid @enderror"
                                       value="{{ old('applicant_name') }}" required>
                                @error('applicant_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="applicant_email" class="form-control @error('applicant_email') is-invalid @enderror"
                                       value="{{ old('applicant_email') }}" required>
                                @error('applicant_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="applicant_phone" class="form-control" value="{{ old('applicant_phone') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">How did you hear about this role?</label>
                                <input type="text" name="source" class="form-control" value="{{ old('source') }}"
                                       placeholder="e.g. LinkedIn, Referral…">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Resume / CV <span class="text-muted small">(PDF, DOC, DOCX — max 5MB)</span></label>
                                <input type="file" name="resume" class="form-control @error('resume') is-invalid @enderror"
                                       accept=".pdf,.doc,.docx">
                                @error('resume')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cover Letter</label>
                                <textarea name="cover_letter" class="form-control" rows="5"
                                          placeholder="Tell us why you're a great fit…">{{ old('cover_letter') }}</textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-4">Submit Application</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
