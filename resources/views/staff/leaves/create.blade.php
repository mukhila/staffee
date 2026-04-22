<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Apply for Leave</h1>
                <span>Submit a leave request</span>
            </div>
            <a href="{{ route('staff.leaves.index') }}" class="btn btn-outline-secondary waves-effect">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.leaves.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-medium">Leave Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">Select type</option>
                                    <option value="sick" {{ old('type') === 'sick' ? 'selected' : '' }}>Sick Leave</option>
                                    <option value="casual" {{ old('type') === 'casual' ? 'selected' : '' }}>Casual Leave</option>
                                    <option value="annual" {{ old('type') === 'annual' ? 'selected' : '' }}>Annual Leave</option>
                                    <option value="unpaid" {{ old('type') === 'unpaid' ? 'selected' : '' }}>Unpaid Leave</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">From Date <span class="text-danger">*</span></label>
                                    <input type="date" name="from_date" class="form-control @error('from_date') is-invalid @enderror"
                                        value="{{ old('from_date') }}" min="{{ now()->format('Y-m-d') }}" required>
                                    @error('from_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">To Date <span class="text-danger">*</span></label>
                                    <input type="date" name="to_date" class="form-control @error('to_date') is-invalid @enderror"
                                        value="{{ old('to_date') }}" min="{{ now()->format('Y-m-d') }}" required>
                                    @error('to_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="4"
                                    placeholder="Provide a reason for your leave request..." required>{{ old('reason') }}</textarea>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">Submit Request</button>
                                <a href="{{ route('staff.leaves.index') }}" class="btn btn-outline-secondary waves-effect">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
