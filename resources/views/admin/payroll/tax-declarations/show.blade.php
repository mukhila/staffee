<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Tax Declaration</h1>
            <span>{{ $declaration->user?->name }} &mdash; FY {{ $declaration->fiscal_year }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if($declaration->isSubmitted())
            <form method="POST" action="{{ route('admin.payroll.tax-declarations.verify', $declaration) }}">
                @csrf
                <button type="submit" class="btn btn-success btn-sm"
                    onclick="return confirm('Mark as verified?')">
                    <i class="fi fi-rr-check me-1"></i> Verify Declaration
                </button>
            </form>
            @endif
            <a href="{{ route('admin.payroll.tax-declarations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if($errors->has('general'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ $errors->first('general') }}</div>
    @endif

    <div class="row g-4">
        {{-- Left: declaration details --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light"><h6 class="mb-0">Declaration Details</h6></div>
                <div class="card-body">
                    @php
                    $statusColors = [
                        'draft'     => 'secondary',
                        'submitted' => 'info',
                        'verified'  => 'success',
                        'locked'    => 'dark',
                    ];
                    $sc = $statusColors[$declaration->declaration_status] ?? 'secondary';
                    @endphp
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted small">Employee</dt>
                        <dd class="col-7 fw-medium">{{ $declaration->user?->name }}</dd>

                        <dt class="col-5 text-muted small">Email</dt>
                        <dd class="col-7 small">{{ $declaration->user?->email }}</dd>

                        <dt class="col-5 text-muted small">Fiscal Year</dt>
                        <dd class="col-7">{{ $declaration->fiscal_year }}</dd>

                        <dt class="col-5 text-muted small">Tax Regime</dt>
                        <dd class="col-7 small">{{ $declaration->taxRegime?->name ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Status</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($declaration->declaration_status) }}</span>
                        </dd>

                        <dt class="col-5 text-muted small">Submitted</dt>
                        <dd class="col-7 small">{{ $declaration->submitted_at?->format('d M Y H:i') ?? '—' }}</dd>

                        @if($declaration->verifier)
                        <dt class="col-5 text-muted small">Verified By</dt>
                        <dd class="col-7 small">{{ $declaration->verifier->name }}</dd>

                        <dt class="col-5 text-muted small">Verified At</dt>
                        <dd class="col-7 small">{{ $declaration->verified_at?->format('d M Y H:i') ?? '—' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Proof status --}}
            <div class="card mt-3">
                <div class="card-header bg-light"><h6 class="mb-0">Proof Status</h6></div>
                <div class="card-body">
                    @php $proofStatus = (array) ($declaration->proof_status ?? []); @endphp
                    @if(empty($proofStatus))
                    <p class="text-muted small mb-0">No proofs uploaded yet.</p>
                    @else
                    @foreach($proofStatus as $section => $status)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="badge bg-primary-subtle text-primary">{{ $section }}</span>
                        <span class="badge bg-success-subtle text-success small">{{ ucfirst($status) }}</span>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: declared amounts + proof files --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Declared Amounts</h6>
                    <span class="fw-medium">
                        Total: <strong>₹{{ number_format((float) $declaration->totalDeclared(), 2) }}</strong>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Section</th>
                                    <th class="text-end">Declared Amount (₹)</th>
                                    <th class="text-center">Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sectionLabels = [
                                    '80C'  => 'Section 80C',
                                    '80D'  => 'Section 80D',
                                    '80E'  => 'Section 80E',
                                    '80G'  => 'Section 80G',
                                    'HRA'  => 'HRA Exemption',
                                    'LTA'  => 'LTA Exemption',
                                    'OTHER'=> 'Other Deductions',
                                ]; @endphp
                                @foreach((array) $declaration->declared_amounts as $section => $amount)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary me-1">{{ $section }}</span>
                                        <span class="small text-muted">{{ $sectionLabels[$section] ?? $section }}</span>
                                    </td>
                                    <td class="text-end fw-medium">{{ number_format((float) $amount, 2) }}</td>
                                    <td class="text-center">
                                        @if(isset($proofStatus[$section]) && $proofStatus[$section] === 'uploaded')
                                        <i class="fi fi-rr-check-circle text-success" title="Proof uploaded"></i>
                                        @else
                                        <i class="fi fi-rr-cross-circle text-danger opacity-50" title="No proof"></i>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Proof files --}}
            @if($declaration->proofs->count())
            <div class="card mt-3">
                <div class="card-header bg-light"><h6 class="mb-0">Proof Files</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Section</th>
                                    <th>File Name</th>
                                    <th>Uploaded</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($declaration->proofs as $proof)
                                <tr>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $proof->section }}</span></td>
                                    <td class="small">{{ $proof->original_name }}</td>
                                    <td class="text-muted small">{{ $proof->uploaded_at?->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
