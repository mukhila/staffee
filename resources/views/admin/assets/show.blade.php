<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.assets.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="fi fi-rr-arrow-left"></i>
                </a>
                <h4 class="mb-0">{{ $asset->name }} <small class="text-muted">{{ $asset->asset_tag }}</small></h4>
            </div>
            <a href="{{ route('admin.assets.edit', $asset) }}" class="btn btn-outline-primary btn-sm">
                <i class="fi fi-rr-edit me-1"></i> Edit
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-4">
            {{-- Details --}}
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header"><strong>Asset Details</strong></div>
                    <div class="card-body">
                        @php
                            $badgeClass = match($asset->status) {
                                'available' => 'success',
                                'assigned'  => 'primary',
                                'in_repair' => 'warning',
                                'retired'   => 'secondary',
                                'lost'      => 'danger',
                                default     => 'secondary',
                            };
                        @endphp
                        <dl class="row mb-0">
                            <dt class="col-5">Status</dt>
                            <dd class="col-7"><span class="badge bg-{{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $asset->status)) }}</span></dd>
                            <dt class="col-5">Category</dt>
                            <dd class="col-7">{{ ucfirst(str_replace('_', ' ', $asset->category)) }}</dd>
                            <dt class="col-5">Brand</dt>
                            <dd class="col-7">{{ $asset->brand ?? '—' }}</dd>
                            <dt class="col-5">Model</dt>
                            <dd class="col-7">{{ $asset->model_number ?? '—' }}</dd>
                            <dt class="col-5">Serial No.</dt>
                            <dd class="col-7">{{ $asset->serial_number ?? '—' }}</dd>
                            <dt class="col-5">Location</dt>
                            <dd class="col-7">{{ $asset->location ?? '—' }}</dd>
                            <dt class="col-5">Purchase Date</dt>
                            <dd class="col-7">{{ $asset->purchase_date?->format('d M Y') ?? '—' }}</dd>
                            <dt class="col-5">Purchase Cost</dt>
                            <dd class="col-7">{{ $asset->purchase_cost ? number_format($asset->purchase_cost, 2) : '—' }}</dd>
                            <dt class="col-5">Warranty</dt>
                            <dd class="col-7">{{ $asset->warranty_expiry?->format('d M Y') ?? '—' }}</dd>
                            @if($asset->notes)
                            <dt class="col-5">Notes</dt>
                            <dd class="col-7">{{ $asset->notes }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="col-md-7">
                {{-- Assign --}}
                @if($asset->status === 'available')
                <div class="card mb-3">
                    <div class="card-header"><strong>Assign to User</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.assets.assign', $asset) }}" class="row g-2">
                            @csrf
                            <div class="col-md-8">
                                <select name="user_id" class="form-select" required>
                                    <option value="">Select employee</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">Assign</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Return --}}
                @if($asset->currentAssignment)
                <div class="card mb-3">
                    <div class="card-header"><strong>Current Assignment</strong></div>
                    <div class="card-body">
                        <p class="mb-2">Assigned to: <strong>{{ $asset->currentAssignment->user?->name }}</strong> on {{ $asset->currentAssignment->assigned_at->format('d M Y') }}</p>
                        <form method="POST" action="{{ route('admin.asset-assignments.return', $asset->currentAssignment) }}" class="row g-2">
                            @csrf
                            <div class="col-md-4">
                                <select name="return_condition" class="form-select" required>
                                    <option value="">Condition</option>
                                    <option value="good">Good</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="return_notes" class="form-control" placeholder="Notes (optional)">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-warning w-100">Return</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Send for Repair --}}
                @if(in_array($asset->status, ['available', 'assigned']))
                <div class="card mb-3">
                    <div class="card-header"><strong>Maintenance</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.assets.repair', $asset) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning"
                                onclick="return confirm('Send this asset for repair?')">
                                <i class="fi fi-rr-tools me-1"></i> Send for Repair
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Assignment History --}}
        <div class="card mt-4">
            <div class="card-header"><strong>Assignment History</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Assigned By</th>
                                <th>Assigned At</th>
                                <th>Returned At</th>
                                <th>Condition</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($asset->assignments->sortByDesc('assigned_at') as $a)
                            <tr>
                                <td>{{ $a->user?->name }}</td>
                                <td>{{ $a->assignedBy?->name }}</td>
                                <td>{{ $a->assigned_at->format('d M Y H:i') }}</td>
                                <td>{{ $a->returned_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>{{ $a->return_condition ? ucfirst($a->return_condition) : '—' }}</td>
                                <td>{{ $a->return_notes ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No assignment history.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
