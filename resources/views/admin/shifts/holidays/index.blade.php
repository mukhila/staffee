<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Shift Holidays</h1>
            <span>Non-working days — shift validation is skipped on these dates</span>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            <i class="fi fi-rr-plus me-1"></i> Add Holiday
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Recurring</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays as $h)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $h->date->format('d M Y') }}</div>
                                <div class="text-muted small">{{ $h->date->format('l') }}</div>
                            </td>
                            <td>{{ $h->name }}</td>
                            <td>
                                <span class="badge bg-{{ $h->type_color }}-subtle text-{{ $h->type_color }}">
                                    {{ ucfirst($h->holiday_type) }}
                                </span>
                            </td>
                            <td>
                                @if($h->is_recurring)
                                <span class="badge bg-secondary-subtle text-secondary">Yearly</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $h->is_active ? 'success' : 'secondary' }}-subtle text-{{ $h->is_active ? 'success' : 'secondary' }}">
                                    {{ $h->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.shifts.holidays.destroy', $h) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this holiday?')">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No holidays configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($holidays->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $holidays->links() }}</div>
        @endif
    </div>
</div>

{{-- Add Holiday Modal --}}
<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('admin.shifts.holidays.store') }}" method="POST">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Add Holiday</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Diwali" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select name="holiday_type" class="form-select">
                            <option value="national">National</option>
                            <option value="regional">Regional</option>
                            <option value="company">Company</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_recurring" value="1" class="form-check-input" id="isRecurring">
                            <label class="form-check-label" for="isRecurring">Repeat every year (same month & day)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Holiday</button>
            </div>
        </form>
    </div></div>
</div>
</x-app-layout>
