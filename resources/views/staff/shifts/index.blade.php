<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">My Shift</h1>
            <span>View your current shift and submit change requests</span>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#shiftRequestModal">
            <i class="fi fi-rr-arrows-alt-h me-1"></i> Request Change
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    {{-- Current Shift --}}
    <div class="card mb-4">
        <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Current Shift Assignment</h6></div>
        <div class="card-body">
            @if($currentAssignment && $currentAssignment->shift)
            @php $shift = $currentAssignment->shift; @endphp
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small mb-1">Shift Name</div>
                    <div class="fw-bold fs-5">{{ $shift->name }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small mb-1">Start Time</div>
                    <div class="fw-medium">{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small mb-1">End Time</div>
                    <div class="fw-medium">{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small mb-1">Assigned Since</div>
                    <div class="fw-medium">{{ $currentAssignment->effective_from->format('d M Y') }}</div>
                </div>
            </div>
            @else
            <div class="text-muted text-center py-3">
                <i class="fi fi-rr-calendar-clock fs-3 d-block mb-2 opacity-25"></i>
                No active shift assignment found. Contact HR.
            </div>
            @endif
        </div>
    </div>

    {{-- Change Request History --}}
    <h6 class="fw-semibold mb-3">Change Request History</h6>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Submitted</th>
                            <th>Type</th>
                            <th>Current Shift</th>
                            <th>Requested Shift / Swap</th>
                            <th>Effective Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changeRequests as $cr)
                        <tr>
                            <td class="text-muted small">{{ $cr->created_at->format('d M Y') }}</td>
                            <td>
                                @if($cr->swap_with_user_id)
                                <span class="badge bg-info-subtle text-info">Swap</span>
                                @else
                                <span class="badge bg-primary-subtle text-primary">Change</span>
                                @endif
                            </td>
                            <td>{{ $cr->currentShift?->name ?? '—' }}</td>
                            <td>
                                @if($cr->swap_with_user_id)
                                Swap with <strong>{{ $cr->swapWithUser?->name ?? '—' }}</strong>
                                @else
                                {{ $cr->requestedShift?->name ?? '—' }}
                                @endif
                            </td>
                            <td>{{ $cr->effective_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $cr->status_color }}-subtle text-{{ $cr->status_color }}">
                                    {{ ucfirst($cr->status) }}
                                </span>
                            </td>
                            <td>
                                @if($cr->isPending())
                                <form action="{{ route('staff.shifts.cancel-request', $cr) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Cancel this request?')">Cancel</button>
                                </form>
                                @elseif($cr->manager_notes)
                                <span class="text-muted small fst-italic">{{ Str::limit($cr->manager_notes, 30) }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fi fi-rr-arrows-alt-h fs-3 d-block mb-2 opacity-25"></i>
                                No shift change requests yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($changeRequests->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $changeRequests->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Shift Request Modal --}}
<div class="modal fade" id="shiftRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.shifts.change-request') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Request Shift Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2"><ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Request Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="request_type" id="typeChange" value="change" checked>
                                <label class="form-check-label" for="typeChange">Shift Change</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="request_type" id="typeSwap" value="swap">
                                <label class="form-check-label" for="typeSwap">Swap with Colleague</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="requestedShiftField">
                        <label class="form-label">Requested Shift <span class="text-danger">*</span></label>
                        <select name="requested_shift_id" class="form-select">
                            <option value="">— Select Shift —</option>
                            @foreach($availableShifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="swapUserField">
                        <label class="form-label">Swap With <span class="text-danger">*</span></label>
                        <select name="swap_with_user_id" class="form-select">
                            <option value="">— Select Colleague —</option>
                            @foreach($swapCandidates as $colleague)
                            <option value="{{ $colleague->id }}">{{ $colleague->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control"
                               min="{{ today()->format('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                                  placeholder="Explain why you need this change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fi fi-rr-paper-plane me-1"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="request_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var isSwap = this.value === 'swap';
        document.getElementById('requestedShiftField').classList.toggle('d-none', isSwap);
        document.getElementById('swapUserField').classList.toggle('d-none', !isSwap);
    });
});
</script>

@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('shiftRequestModal')).show();
});
</script>
@endif
</x-app-layout>
