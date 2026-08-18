<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">
                {{ $existing ? 'Edit Draft Declaration' : 'New Tax Declaration' }}
            </h1>
            <span>Declare your investments and exemptions for FY {{ $currentYear }}</span>
        </div>
        <a href="{{ route('staff.tax-declarations.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if($errors->has('general'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ $errors->first('general') }}</div>
    @endif
    @if($existing && !$existing->isDraft())
    <div class="alert alert-warning">
        <i class="fi fi-rr-info me-1"></i>
        This declaration has status <strong>{{ ucfirst($existing->declaration_status) }}</strong> and cannot be edited.
    </div>
    @endif

    @php
    $sections = [
        '80C'  => 'Section 80C (ELSS, PPF, LIC, PF, etc.)',
        '80D'  => 'Section 80D (Medical Insurance)',
        '80E'  => 'Section 80E (Education Loan Interest)',
        '80G'  => 'Section 80G (Donations)',
        'HRA'  => 'HRA Exemption',
        'LTA'  => 'LTA Exemption',
        'OTHER'=> 'Other Deductions',
    ];
    $amounts = old('declared_amounts', $existing?->declared_amounts ?? []);
    $locked  = $existing && !$existing->isDraft();
    @endphp

    <form method="POST" action="{{ route('staff.tax-declarations.store') }}">
        @csrf

        <div class="row g-4">
            {{-- Left column: regime + fiscal year --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Declaration Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                            <input type="text" name="fiscal_year" value="{{ old('fiscal_year', $currentYear) }}"
                                class="form-control @error('fiscal_year') is-invalid @enderror"
                                placeholder="2025-26" {{ $locked ? 'disabled' : '' }}>
                            @error('fiscal_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tax Regime <span class="text-danger">*</span></label>
                            <select name="tax_regime_id" class="form-select @error('tax_regime_id') is-invalid @enderror" {{ $locked ? 'disabled' : '' }}>
                                <option value="">Select regime…</option>
                                @foreach($taxRegimes as $regime)
                                <option value="{{ $regime->id }}"
                                    {{ old('tax_regime_id', $existing?->tax_regime_id) == $regime->id ? 'selected' : '' }}>
                                    {{ $regime->name }} ({{ $regime->fiscal_year }})
                                </option>
                                @endforeach
                            </select>
                            @error('tax_regime_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($existing)
                        <div class="mt-3">
                            <div class="text-muted small">Status</div>
                            @php
                            $statusColors = ['draft'=>'secondary','submitted'=>'info','verified'=>'success','locked'=>'dark'];
                            $sc = $statusColors[$existing->declaration_status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} fs-6">{{ ucfirst($existing->declaration_status) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Proof upload (if declaration exists) --}}
                @if($existing)
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Upload Proof Documents</h6>
                    </div>
                    <div class="card-body">
                        @if($existing->isVerified() || $existing->isLocked())
                        <p class="text-muted small">Proof upload not available for {{ $existing->declaration_status }} declarations.</p>
                        @else
                        <form method="POST" action="{{ route('staff.tax-declarations.proof', $existing) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Section</label>
                                <select name="section" class="form-select form-select-sm">
                                    @foreach($sections as $key => $label)
                                    <option value="{{ $key }}">{{ $key }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">File (PDF/JPG/PNG, max 5 MB)</label>
                                <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Upload</button>
                        </form>

                        @if($existing->proofs && $existing->proofs->count())
                        <hr class="my-2">
                        <div class="small fw-medium mb-1">Uploaded:</div>
                        @foreach($existing->proofs as $proof)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span class="badge bg-primary-subtle text-primary me-2">{{ $proof->section }}</span>
                            <span class="text-truncate">{{ $proof->original_name }}</span>
                        </div>
                        @endforeach
                        @endif
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Right column: declared amounts --}}
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Declared Amounts (INR)</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($sections as $key => $label)
                            <div class="col-md-6">
                                <label class="form-label small">{{ $label }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="declared_amounts[{{ $key }}]"
                                        step="0.01" min="0"
                                        value="{{ old('declared_amounts.'.$key, isset($amounts[$key]) ? number_format((float) $amounts[$key], 2, '.', '') : '') }}"
                                        class="form-control @error('declared_amounts.'.$key) is-invalid @enderror"
                                        placeholder="0.00"
                                        {{ $locked ? 'disabled' : '' }}>
                                </div>
                                @error('declared_amounts.'.$key)
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            @endforeach
                        </div>

                        @if(!$locked)
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fi fi-rr-disk me-1"></i> Save Draft
                            </button>
                            <a href="{{ route('staff.tax-declarations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</x-app-layout>
