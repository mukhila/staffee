<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Resignation: {{ $resignation->employee->name }}</h1>
            <span>{{ ucwords(str_replace('_', ' ', $resignation->resignation_type)) }} · Submitted {{ $resignation->submitted_date->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            @php
            $statusColors = [
                'manager_reviewing' => 'warning',
                'manager_accepted'  => 'info',
                'manager_rejected'  => 'danger',
                'hr_approved'       => 'success',
                'notice_period'     => 'primary',
                'completed'         => 'success',
                'withdrawn'         => 'secondary',
            ];
            $c = $statusColors[$resignation->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $c }} fs-6">{{ ucwords(str_replace('_', ' ', $resignation->status)) }}</span>
            <a href="{{ route('admin.hr.resignations.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        {{-- Left: Info + Actions --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal">Employee</th><td>{{ $resignation->employee->name }}</td></tr>
                        <tr><th class="text-muted fw-normal">Department</th><td>{{ $resignation->employee->department?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Submitted</th><td>{{ $resignation->submitted_date->format('d M Y') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Requested Last Date</th><td>{{ $resignation->requested_last_date->format('d M Y') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Notice Period</th>
                            <td>
                                {{ $resignation->notice_period_days }} days
                                @if($resignation->notice_waived)
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Waived</span>
                                @endif
                            </td>
                        </tr>
                        @if($resignation->official_last_date)
                        <tr><th class="text-muted fw-normal">Official Last Date</th><td class="fw-medium">{{ $resignation->official_last_date->format('d M Y') }}</td></tr>
                        @endif
                        <tr><th class="text-muted fw-normal">Manager</th><td>{{ $resignation->manager?->name ?? '—' }}</td></tr>
                        @if($resignation->hrReviewer)
                        <tr><th class="text-muted fw-normal">HR Reviewer</th><td>{{ $resignation->hrReviewer->name }}</td></tr>
                        @endif
                    </table>
                    <hr>
                    <p class="small text-muted mb-0"><strong>Reason:</strong> {{ $resignation->reason }}</p>
                    @if($resignation->notice_waived && $resignation->waiver_reason)
                    <p class="small text-muted mt-2 mb-0"><strong>Waiver Reason:</strong> {{ $resignation->waiver_reason }}</p>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Actions</h6></div>
                <div class="card-body d-flex flex-column gap-2">

                    @if($resignation->status === 'manager_reviewing')
                    <form action="{{ route('admin.hr.resignations.manager-decision', $resignation) }}" method="POST">
                        @csrf
                        <textarea name="manager_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Manager notes (optional)"></textarea>
                        <div class="d-flex gap-2">
                            <button type="submit" name="decision" value="accepted" class="btn btn-success flex-grow-1">Accept</button>
                            <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger flex-grow-1"
                                    onclick="return confirm('Reject this resignation?')">Reject</button>
                        </div>
                    </form>
                    @endif

                    @if($resignation->status === 'manager_accepted')
                    <form action="{{ route('admin.hr.resignations.hr-approve', $resignation) }}" method="POST">
                        @csrf
                        <button class="btn btn-primary w-100" onclick="return confirm('Approve and commence notice period?')">
                            <i class="fi fi-rr-check me-1"></i> HR Approve & Start Notice
                        </button>
                    </form>
                    @endif

                    @if($resignation->termination)
                    <a href="{{ route('admin.hr.terminations.show', $resignation->termination) }}" class="btn btn-outline-secondary">
                        <i class="fi fi-rr-user-minus me-1"></i> View Termination Record
                    </a>
                    @endif

                    @if($resignation->isWithdrawable())
                    <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalWithdraw">
                        Withdraw Resignation
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Timeline --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Workflow Timeline</h6></div>
                <div class="card-body">
                    @php
                    $steps = [
                        ['label'    => 'Resignation Submitted',
                         'done'     => true,
                         'by'       => $resignation->employee->name,
                         'at'       => $resignation->submitted_date,
                         'notes'    => null,
                        ],
                        ['label'    => 'Manager Review',
                         'done'     => in_array($resignation->status, ['manager_accepted','hr_approved','notice_period','completed']),
                         'rejected' => $resignation->status === 'manager_rejected',
                         'by'       => $resignation->manager?->name,
                         'at'       => $resignation->manager_reviewed_at,
                         'notes'    => $resignation->manager_notes,
                        ],
                        ['label'    => 'HR Approval',
                         'done'     => in_array($resignation->status, ['hr_approved','notice_period','completed']),
                         'rejected' => false,
                         'by'       => $resignation->hrReviewer?->name,
                         'at'       => $resignation->hr_reviewed_at,
                         'notes'    => null,
                        ],
                        ['label'    => 'Notice Period',
                         'done'     => in_array($resignation->status, ['notice_period','completed']),
                         'rejected' => false,
                         'by'       => null,
                         'at'       => $resignation->official_last_date ? $resignation->official_last_date->copy()->subDays($resignation->notice_period_days) : null,
                         'notes'    => $resignation->official_last_date ? 'Ends: ' . $resignation->official_last_date->format('d M Y') : null,
                        ],
                        ['label'    => 'Completed',
                         'done'     => $resignation->status === 'completed',
                         'rejected' => $resignation->status === 'withdrawn',
                         'by'       => null,
                         'at'       => null,
                         'notes'    => $resignation->status === 'withdrawn' ? ('Withdrawn' . ($resignation->withdrawal_reason ? ': ' . $resignation->withdrawal_reason : '')) : null,
                        ],
                    ];
                    @endphp

                    <div class="timeline">
                        @foreach($steps as $step)
                        <div class="d-flex gap-3 mb-4">
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width:32px;height:32px;background:{{ ($step['rejected'] ?? false) ? '#dc354520' : ($step['done'] ? '#19875420' : '#6c757d20') }}">
                                    @if($step['rejected'] ?? false)
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
                                <div class="fw-medium {{ ($step['rejected'] ?? false) ? 'text-danger' : ($step['done'] ? '' : 'text-muted') }}">
                                    {{ $step['label'] }}
                                </div>
                                @if($step['by'] ?? null)
                                <div class="text-muted small">
                                    {{ $step['by'] }}
                                    @if($step['at'] ?? null) · {{ \Carbon\Carbon::parse($step['at'])->format('d M Y') }} @endif
                                </div>
                                @elseif(!$step['done'] && !($step['rejected'] ?? false))
                                <div class="text-muted small">Pending</div>
                                @endif
                                @if($step['notes'] ?? null)
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

    {{-- Withdraw Modal --}}
    @if($resignation->isWithdrawable())
    <div class="modal fade" id="modalWithdraw" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('admin.hr.resignations.withdraw', $resignation) }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Withdraw Resignation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for withdrawal</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="State the reason for withdrawing this resignation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Withdraw this resignation?')">Withdraw</button>
                </div>
            </form>
        </div></div>
    </div>
    @endif

</div>
</x-app-layout>
