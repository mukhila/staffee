<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <h1 class="app-page-title">Add Billable Rate</h1>
        <a href="{{ route('admin.time.rates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card" style="max-width:600px" x-data="{ rateType: '{{ old('rate_type', 'global') }}' }">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.time.rates.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Rate Type <span class="text-danger">*</span></label>
                    <select name="rate_type" class="form-select @error('rate_type') is-invalid @enderror"
                            required x-model="rateType">
                        @foreach(\App\Models\BillableRate::RATE_TYPES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('rate_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3" x-show="rateType === 'user' || rateType === 'user_project'" x-cloak>
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                        <option value="">— Select employee —</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3" x-show="rateType === 'project' || rateType === 'user_project'" x-cloak>
                    <label class="form-label">Project <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                        <option value="">— Select project —</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Hourly Rate <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="hourly_rate" class="form-control @error('hourly_rate') is-invalid @enderror"
                                   value="{{ old('hourly_rate') }}" min="0" step="0.01" max="9999.99" required>
                            @error('hourly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency', 'USD') }}" maxlength="3" style="text-transform:uppercase">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror"
                               value="{{ old('effective_from', now()->format('Y-m-d')) }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror"
                               value="{{ old('effective_to') }}">
                        <div class="form-text">Leave blank for open-ended (current rate).</div>
                        @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="500">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Rate</button>
                    <a href="{{ route('admin.time.rates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
