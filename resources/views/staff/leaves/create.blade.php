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

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-3">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.leaves.store') }}" x-data="leaveForm()">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-medium">Leave Type <span class="text-danger">*</span></label>
                                <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror"
                                        required x-model="typeId" @change="onTypeChange()">
                                    <option value="">— Select type —</option>
                                    @foreach($types as $type)
                                    <option value="{{ $type->id }}"
                                            data-half="{{ $type->allow_half_day ? '1' : '0' }}"
                                            data-color="{{ $type->color }}"
                                            {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                        @if($balances->has($type->id))
                                        ({{ $balances[$type->id]->effective_available }} days available)
                                        @endif
                                    </option>
                                    @endforeach
                                </select>
                                @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">From Date <span class="text-danger">*</span></label>
                                    <input type="date" name="from_date" class="form-control @error('from_date') is-invalid @enderror"
                                           value="{{ old('from_date') }}" min="{{ now()->format('Y-m-d') }}"
                                           required x-model="fromDate" @change="recalc()">
                                    @error('from_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">To Date <span class="text-danger">*</span></label>
                                    <input type="date" name="to_date" class="form-control @error('to_date') is-invalid @enderror"
                                           value="{{ old('to_date') }}" :min="fromDate || '{{ now()->format('Y-m-d') }}'"
                                           required x-model="toDate" @change="recalc()">
                                    @error('to_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            {{-- Half day option (shown only if type supports it) --}}
                            <div class="mb-3" x-show="allowHalfDay && fromDate && fromDate === toDate" x-cloak>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="half_day" id="half_day"
                                           value="1" x-model="halfDay" {{ old('half_day') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="half_day">Half day</label>
                                </div>
                                <div x-show="halfDay" class="d-inline-flex gap-3 ms-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="half_day_period" value="morning" id="hd_morning"
                                               {{ old('half_day_period', 'morning') === 'morning' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hd_morning">Morning</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="half_day_period" value="afternoon" id="hd_afternoon"
                                               {{ old('half_day_period') === 'afternoon' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="hd_afternoon">Afternoon</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                                          rows="4" placeholder="Briefly explain your reason..." required>{{ old('reason') }}</textarea>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">
                                    <i class="fi fi-rr-paper-plane me-1"></i> Submit Request
                                </button>
                                <a href="{{ route('staff.leaves.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Balance sidebar --}}
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Your Balances ({{ now()->year }})</h6></div>
                    <div class="card-body p-0">
                        @if($balances->isEmpty())
                        <p class="text-muted small p-3 mb-0">No balance records yet. Submit a request and balances will be initialised.</p>
                        @else
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Type</th><th class="text-end">Avail.</th><th class="text-end">Used</th></tr>
                            </thead>
                            <tbody>
                                @foreach($types as $type)
                                @if($balances->has($type->id))
                                @php $bal = $balances[$type->id]; @endphp
                                <tr>
                                    <td>
                                        <span class="rounded-circle d-inline-block me-1" style="width:8px;height:8px;background:{{ $type->color }}"></span>
                                        {{ $type->name }}
                                    </td>
                                    <td class="text-end fw-bold text-{{ $bal->effective_available > 3 ? 'success' : ($bal->effective_available > 0 ? 'warning' : 'danger') }}">
                                        {{ $bal->effective_available }}
                                    </td>
                                    <td class="text-end text-muted">{{ $bal->used_days }}</td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function leaveForm() {
    return {
        typeId: '{{ old('leave_type_id', '') }}',
        fromDate: '{{ old('from_date', '') }}',
        toDate: '{{ old('to_date', '') }}',
        halfDay: {{ old('half_day') ? 'true' : 'false' }},
        allowHalfDay: false,

        onTypeChange() {
            const sel = document.querySelector(`select[name=leave_type_id] option[value="${this.typeId}"]`);
            this.allowHalfDay = sel ? sel.dataset.half === '1' : false;
            if (!this.allowHalfDay) this.halfDay = false;
        },

        recalc() {
            if (this.fromDate && this.toDate && this.fromDate > this.toDate) {
                this.toDate = this.fromDate;
            }
        },
    };
}
</script>
</x-app-layout>
