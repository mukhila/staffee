<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Promotion: {{ $promotion->employee->name }}</h1>
            <span>{{ $promotion->current_designation ?? ucfirst($promotion->current_role) }} → {{ $promotion->proposed_designation ?? ucfirst($promotion->proposed_role) }}</span>
        </div>
        <div class="d-flex gap-2">
            @php
            $statusColors = [
                'pending_manager'  => 'warning',
                'manager_approved' => 'info',
                'manager_rejected' => 'danger',
                'hr_approved'      => 'primary',
                'hr_rejected'      => 'danger',
                'finance_approved' => 'success',
                'finance_rejected' => 'danger',
                'applied'          => 'success',
                'withdrawn'        => 'secondary',
            ];
            $c = $statusColors[$promotion->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $c }} fs-6">{{ ucwords(str_replace('_', ' ', $promotion->status)) }}</span>
            <a href="{{ route('admin.hr.promotions.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        {{-- Left: Details + Actions --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal">Employee</th><td>{{ $promotion->employee->name }}</td></tr>
                        <tr><th class="text-muted fw-normal">Proposed By</th><td>{{ $promotion->proposedBy?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Effective Date</th><td>{{ $promotion->effective_date->format('d M Y') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Department</th><td>{{ $promotion->currentDepartment?->name ?? '—' }} → {{ $promotion->proposedDepartment?->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Salary comparison --}}
            @if($promotion->current_salary || $promotion->proposed_salary)
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Salary Change</h6></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Current</span>
                        <span class="fw-medium">{{ number_format($promotion->current_salary, 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Proposed</span>
                        <span class="fw-medium text-success">{{ number_format($promotion->proposed_salary, 0) }}</span>
                    </div>
                    @if($promotion->current_salary > 0)
                    @php $pct = $promotion->salary_increase_percent; @endphp
                    <div class="alert alert-{{ $pct >= 0 ? 'success' : 'danger' }} py-2 mb-0 text-center">
                        <strong>{{ $pct >= 0 ? '+' : '' }}{{ number_format($pct, 1) }}% change</strong>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Reason --}}
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Reason</h6></div>
                <div class="card-body">
                    <p class="small mb-0">{{ $promotion->reason }}</p>
                </div>
            </div>

            {{-- Actions --}}
            @if($promotion->isPending())
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Decision</h6></div>
                <div class="card-body">
                    @php
                    $stepLabel = match($promotion->status) {
                        'pending_manager' => 'Manager Review',
                        'manager_approved'=> 'HR Review',
                        'hr_approved'     => 'Finance Review',
                        default           => 'Review',
                    };
                    @endphp
                    <p class="text-muted small mb-3">Current stage: <strong>{{ $stepLabel }}</strong></p>
                    <form action="{{ route('admin.hr.promotions.approve', $promotion) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Notes (optional)"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="decision" value="approved" class="btn btn-success flex-grow-1">
                                <i class="fi fi-rr-check me-1"></i> Approve
                            </button>
                            <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger flex-grow-1"
                                    onclick="return confirm('Reject this promotion?')">
                                <i class="fi fi-rr-cross me-1"></i> Reject
                            </button>
                        </div>
                    </form>

                    <form action="{{ route('admin.hr.promotions.destroy', $promotion) }}" method="POST" class="mt-2">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-secondary w-100 btn-sm"
                                onclick="return confirm('Withdraw this promotion proposal?')">
                            Withdraw Proposal
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Approval timeline --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Approval Timeline</h6></div>
                <div class="card-body">

                    @php
                    $steps = [
                        ['label' => 'Manager Approval',
                         'done'  => in_array($promotion->status, ['manager_approved','hr_approved','finance_approved','applied']),
                         'rejected' => $promotion->status === 'manager_rejected',
                         'by'    => $promotion->managerApprovedBy?->name,
                         'at'    => $promotion->manager_approved_at,
                         'notes' => $promotion->manager_notes,
                        ],
                        ['label' => 'HR Approval',
                         'done'  => in_array($promotion->status, ['hr_approved','finance_approved','applied']),
                         'rejected' => $promotion->status === 'hr_rejected',
                         'by'    => $promotion->hrApprovedBy?->name,
                         'at'    => $promotion->hr_approved_at,
                         'notes' => $promotion->hr_notes,
                        ],
                        ['label' => 'Finance Approval',
                         'done'  => in_array($promotion->status, ['finance_approved','applied']),
                         'rejected' => $promotion->status === 'finance_rejected',
                         'by'    => $promotion->financeApprovedBy?->name,
                         'at'    => $promotion->finance_approved_at,
                         'notes' => $promotion->finance_notes,
                        ],
                        ['label' => 'Applied',
                         'done'  => $promotion->status === 'applied',
                         'rejected' => false,
                         'by'    => null,
                         'at'    => $promotion->applied_at ?? null,
                         'notes' => null,
                        ],
                    ];
                    @endphp

                    <div class="timeline">
                        @foreach($steps as $step)
                        <div class="d-flex gap-3 mb-4">
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width:32px;height:32px;background:{{ $step['rejected'] ? '#dc354520' : ($step['done'] ? '#19875420' : '#6c757d20') }}">
                                    @if($step['rejected'])
                                    <i class="fi fi-rr-cross text-danger small"></i>
                                    @elseif($step['done'])
                                    <i class="fi fi-rr-check text-success small"></i>
                                    @else
                                    <i class="fi fi-rr-clock text-muted small"></i>
                                    @endif
                                </div>
                                @if(!$loop->last)
                                <div style="width:2px;flex:1;min-height:24px;background:{{ $step['done'] ? '#19875440' : '#dee2e6' }};margin-top:4px;"></div>
                                @endif
                            </div>
                            <div class="pb-3 flex-grow-1">
                                <div class="fw-medium {{ $step['rejected'] ? 'text-danger' : ($step['done'] ? '' : 'text-muted') }}">
                                    {{ $step['label'] }}
                                    @if($step['rejected']) <span class="badge bg-danger-subtle text-danger ms-1">Rejected</span> @endif
                                </div>
                                @if($step['by'])
                                <div class="text-muted small">{{ $step['by'] }} · {{ $step['at']?->format('d M Y, H:i') }}</div>
                                @elseif(!$step['done'] && !$step['rejected'])
                                <div class="text-muted small">Pending</div>
                                @endif
                                @if($step['notes'])
                                <div class="small mt-1 text-muted fst-italic">"{{ $step['notes'] }}"</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
