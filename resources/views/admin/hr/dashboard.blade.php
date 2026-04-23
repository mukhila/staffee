<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">HR Dashboard</h1>
            <span>People operations overview</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hr.terminations.create') }}" class="btn btn-outline-danger btn-sm">
                <i class="fi fi-rr-user-minus me-1"></i> Initiate Termination
            </a>
            <a href="{{ route('admin.hr.promotions.create') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-arrow-up me-1"></i> New Promotion
            </a>
        </div>
    </div>

    {{-- ── Stat cards ─────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Active Employees',     'value' => $stats['total_active'],       'icon' => 'fi-rr-users',                'color' => 'primary'],
                ['label' => 'On Probation',          'value' => $stats['on_probation'],       'icon' => 'fi-rr-hourglass',            'color' => 'warning'],
                ['label' => 'On Notice Period',      'value' => $stats['on_notice'],          'icon' => 'fi-rr-calendar-exclamation', 'color' => 'danger'],
                ['label' => 'Contracts Expiring',    'value' => $stats['contracts_expiring'], 'icon' => 'fi-rr-file-invoice',         'color' => 'info'],
                ['label' => 'Certs Expiring (60d)',  'value' => $stats['certs_expiring'],     'icon' => 'fi-rr-diploma',              'color' => 'secondary'],
            ];
        @endphp
        @foreach($cards as $card)
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-{{ $card['color'] }}-subtle p-3">
                        <i class="fi {{ $card['icon'] }} text-{{ $card['color'] }} fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $card['value'] }}</div>
                        <div class="text-muted small">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- ── Pending Promotions ──────────────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Pending Promotions</h6>
                    <a href="{{ route('admin.hr.promotions.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0">
                    @forelse($pendingPromotions as $p)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-medium">{{ $p->employee->name }}</div>
                            <div class="text-muted small">
                                {{ $p->current_designation ?? $p->current_role }}
                                → {{ $p->proposed_designation ?? $p->proposed_role }}
                            </div>
                        </div>
                        <span class="badge bg-warning-subtle text-warning">{{ ucwords(str_replace('_', ' ', $p->status)) }}</span>
                        <a href="{{ route('admin.hr.promotions.show', $p) }}" class="btn btn-sm btn-outline-secondary">Review</a>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 mb-0">No pending promotions.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Pending Resignations ────────────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Pending Resignations</h6>
                    <a href="{{ route('admin.hr.resignations.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0">
                    @forelse($pendingResignations as $r)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-medium">{{ $r->employee->name }}</div>
                            <div class="text-muted small">
                                Requested last date: {{ $r->requested_last_date->format('d M Y') }}
                            </div>
                        </div>
                        @php $days = $r->daysRemainingInNotice(); @endphp
                        @if($days !== null)
                        <span class="badge bg-{{ $days <= 7 ? 'danger' : 'info' }}-subtle text-{{ $days <= 7 ? 'danger' : 'info' }}">
                            {{ $days }}d left
                        </span>
                        @endif
                        <a href="{{ route('admin.hr.resignations.show', $r) }}" class="btn btn-sm btn-outline-secondary">Review</a>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 mb-0">No pending resignations.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Active Terminations ─────────────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Active Terminations</h6>
                    <a href="{{ route('admin.hr.terminations.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0">
                    @forelse($activeTerminations as $t)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-medium">{{ $t->employee->name }}</div>
                            <div class="text-muted small">Last date: {{ $t->last_working_date->format('d M Y') }}</div>
                        </div>
                        @php
                            $colors = ['pending_approval'=>'warning','approved'=>'info','processing'=>'primary','settlement_pending'=>'secondary'];
                            $c = $colors[$t->status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucwords(str_replace('_', ' ', $t->status)) }}</span>
                        <a href="{{ route('admin.hr.terminations.show', $t) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 mb-0">No active terminations.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Recent Joinees ──────────────────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h6 class="card-title mb-0">Recent Joinees</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($recentJoinees as $u)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        @if($u->avatar)
                        <img src="{{ asset('storage/' . $u->avatar) }}" class="rounded-circle" width="36" height="36">
                        @else
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:.8rem;">
                            {{ strtoupper(substr($u->name, 0, 2)) }}
                        </div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-medium">{{ $u->name }}</div>
                            <div class="text-muted small">{{ $u->department?->name }} · {{ ucfirst($u->role) }}</div>
                        </div>
                        <div class="text-muted small">{{ $u->profile?->joining_date?->format('d M Y') ?? $u->created_at->format('d M Y') }}</div>
                        <a href="{{ route('admin.hr.employees.show', $u) }}" class="btn btn-sm btn-outline-secondary">Profile</a>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 mb-0">No recent joinees.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
