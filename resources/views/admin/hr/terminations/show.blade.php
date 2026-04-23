<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Termination: {{ $termination->employee->name }}</h1>
            <span>{{ ucwords(str_replace('_',' ',$termination->termination_type)) }} · Last date: {{ $termination->last_working_date->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            @php
                $colors = ['pending_approval'=>'warning','approved'=>'info','processing'=>'primary',
                           'settlement_pending'=>'secondary','settlement_approved'=>'success','completed'=>'success','cancelled'=>'danger'];
                $c = $colors[$termination->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $c }} fs-6">{{ ucwords(str_replace('_',' ',$termination->status)) }}</span>
            <a href="{{ route('admin.hr.terminations.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        {{-- ── Left: details + actions ─────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal">Employee</th><td>{{ $termination->employee->name }}</td></tr>
                        <tr><th class="text-muted fw-normal">Type</th><td>{{ ucwords(str_replace('_',' ',$termination->termination_type)) }}</td></tr>
                        <tr><th class="text-muted fw-normal">Last Date</th><td>{{ $termination->last_working_date->format('d M Y') }}</td></tr>
                        <tr><th class="text-muted fw-normal">Initiated By</th><td>{{ $termination->initiatedBy->name }}</td></tr>
                        <tr><th class="text-muted fw-normal">Settlement</th><td>
                            <span class="badge bg-{{ $termination->settlement_status === 'paid' ? 'success' : 'warning' }}-subtle text-{{ $termination->settlement_status === 'paid' ? 'success' : 'warning' }}">
                                {{ ucwords(str_replace('_',' ',$termination->settlement_status)) }}
                            </span>
                        </td></tr>
                    </table>
                    <hr>
                    <p class="small text-muted mb-0"><strong>Reason:</strong> {{ $termination->reason }}</p>
                </div>
            </div>

            {{-- Workflow Actions --}}
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Actions</h6></div>
                <div class="card-body d-flex flex-column gap-2">
                    @if($termination->status === 'pending_approval')
                    <form action="{{ route('admin.hr.terminations.approve', $termination) }}" method="POST">
                        @csrf
                        <textarea name="approval_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Approval notes (optional)"></textarea>
                        <button class="btn btn-success w-100" onclick="return confirm('Approve this termination?')">
                            <i class="fi fi-rr-check me-1"></i> Approve Termination
                        </button>
                    </form>
                    @endif

                    @if($termination->status === 'processing' && $termination->exitChecklist?->is_complete && !$termination->settlement)
                    <form action="{{ route('admin.hr.terminations.settlement.calculate', $termination) }}" method="POST">
                        @csrf
                        <button class="btn btn-primary w-100">
                            <i class="fi fi-rr-calculator me-1"></i> Calculate Settlement
                        </button>
                    </form>
                    @endif

                    @if($termination->settlement?->status === 'pending_approval')
                    <form action="{{ route('admin.hr.terminations.settlement.approve', $termination) }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-success w-100" onclick="return confirm('Approve this settlement?')">
                            <i class="fi fi-rr-check me-1"></i> Approve Settlement
                        </button>
                    </form>
                    @endif

                    @if($termination->settlement?->status === 'approved' && $termination->status !== 'completed')
                    <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalFinalize">
                        <i class="fi fi-rr-flag me-1"></i> Mark Payment & Finalize
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Right: exit checklist + settlement ──────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Exit Checklist --}}
            @if($termination->exitChecklist)
            @php $checklist = $termination->exitChecklist; @endphp
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Exit Checklist</h6>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress" style="width:120px;height:8px;">
                            <div class="progress-bar bg-success" style="width:{{ $checklist->completionPercentage() }}%"></div>
                        </div>
                        <span class="small text-muted">{{ $checklist->completionPercentage() }}%</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach($checklist->items->groupBy('category') as $category => $items)
                    <div class="px-4 pt-3 pb-1">
                        <div class="text-muted small fw-semibold text-uppercase mb-2">{{ ucwords(str_replace('_',' ',$category)) }}</div>
                        @foreach($items->sortBy('sort_order') as $item)
                        <div class="d-flex align-items-center gap-3 mb-2">
                            @if($item->is_completed)
                                <i class="fi fi-rr-check-circle text-success fs-5"></i>
                            @else
                                <i class="fi fi-rr-circle text-muted fs-5"></i>
                            @endif
                            <div class="flex-grow-1">
                                <span class="{{ $item->is_completed ? 'text-muted text-decoration-line-through' : '' }}">{{ $item->item }}</span>
                                @if($item->is_completed)
                                <div class="small text-muted">Completed by {{ $item->completedBy?->name }} · {{ $item->completed_at?->format('d M Y') }}</div>
                                @endif
                            </div>
                            @if(!$item->is_completed && $termination->status === 'processing')
                            <form action="{{ route('admin.hr.terminations.checklist.complete', [$termination, $item]) }}" method="POST" class="d-flex gap-1">
                                @csrf
                                <input name="notes" class="form-control form-control-sm" style="width:160px;" placeholder="Notes (optional)">
                                <button class="btn btn-sm btn-outline-success">Done</button>
                            </form>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Final Settlement --}}
            @if($termination->settlement)
            @php $s = $termination->settlement; @endphp
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Final Settlement</h6>
                    <span class="badge bg-{{ $s->status === 'paid' ? 'success' : ($s->status === 'approved' ? 'primary' : 'warning') }}">
                        {{ ucwords($s->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">Earnings</h6>
                            <table class="table table-borderless table-sm">
                                <tr><td>Pending Salary ({{ $s->pending_salary_days }} days)</td><td class="text-end">{{ number_format($s->pending_salary_amount, 2) }}</td></tr>
                                @if($s->leave_encashment_amount > 0)
                                <tr><td>Leave Encashment ({{ $s->leave_encashment_days }} days)</td><td class="text-end">{{ number_format($s->leave_encashment_amount, 2) }}</td></tr>
                                @endif
                                @if($s->gratuity > 0)
                                <tr><td>Gratuity</td><td class="text-end">{{ number_format($s->gratuity, 2) }}</td></tr>
                                @endif
                                @foreach($s->other_earnings ?? [] as $e)
                                <tr><td>{{ $e['label'] }}</td><td class="text-end">{{ number_format($e['amount'], 2) }}</td></tr>
                                @endforeach
                                <tr class="fw-bold border-top"><td>Total Earnings</td><td class="text-end text-success">{{ number_format($s->total_earnings, 2) }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger mb-3">Deductions</h6>
                            <table class="table table-borderless table-sm">
                                @if($s->pending_advances > 0)
                                <tr><td>Pending Advances</td><td class="text-end">{{ number_format($s->pending_advances, 2) }}</td></tr>
                                @endif
                                @if($s->notice_shortfall_deduction > 0)
                                <tr><td>Notice Shortfall ({{ $s->notice_shortfall_days }} days)</td><td class="text-end">{{ number_format($s->notice_shortfall_deduction, 2) }}</td></tr>
                                @endif
                                @foreach($s->other_deductions ?? [] as $d)
                                <tr><td>{{ $d['label'] }}</td><td class="text-end">{{ number_format($d['amount'], 2) }}</td></tr>
                                @endforeach
                                <tr class="fw-bold border-top"><td>Total Deductions</td><td class="text-end text-danger">{{ number_format($s->total_deductions, 2) }}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="alert alert-{{ $s->net_payable >= 0 ? 'success' : 'danger' }} d-flex justify-content-between align-items-center mb-0 mt-2">
                        <span class="fw-bold fs-5">Net Payable</span>
                        <span class="fw-bold fs-4">{{ $s->currency }} {{ number_format($s->net_payable, 2) }}</span>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Finalize Modal --}}
    <div class="modal fade" id="modalFinalize" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form action="{{ route('admin.hr.terminations.finalize', $termination) }}" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Finalize Termination</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="text-muted">Record settlement payment to complete the offboarding process. The employee will be deactivated and removed from all active projects.</p>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode *</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Reference</label>
                        <input name="payment_reference" class="form-control" placeholder="Transaction ID / Cheque No.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" onclick="return confirm('This will deactivate the employee permanently. Proceed?')">
                        <i class="fi fi-rr-flag me-1"></i> Finalize
                    </button>
                </div>
            </form>
        </div></div>
    </div>

</div>
</x-app-layout>
