@php
    $f   = $finance;
    $cur = $f['currency'] ? $f['currency'] . ' ' : '';
    $num = fn ($v, $d = 0) => $v === null ? '—' : number_format((float) $v, $d);
    $hrs = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . ' h';
    // green under 80%, amber 80–100%, red over
    $tone = fn (?int $pct) => $pct === null ? 'secondary' : ($pct > 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success'));
    $bar  = fn (?int $pct) => $pct === null ? 0 : min(100, $pct);
@endphp
<div class="card mb-3">
    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Budget and progress</h6>
        @if($f['budget'] === null && $f['budget_hours'] === null)
        <a href="{{ route('admin.projects.edit', $project) }}" class="small">Set a budget</a>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Hours: visible to everyone who can see the project --}}
            <div class="col-md-{{ $canSeeMoney ? '4' : '6' }}">
                <div class="text-muted small mb-1">Hours logged</div>
                <div class="fs-5 fw-bold">{{ $hrs($f['hours']) }}
                    @if($f['budget_hours'] !== null)<span class="text-muted fs-6 fw-normal">of {{ $hrs($f['budget_hours']) }}</span>@endif
                </div>
                @if($f['hours_used_pct'] !== null)
                <div class="progress mt-2" style="height:8px" role="progressbar" aria-label="Hours used" aria-valuenow="{{ $f['hours_used_pct'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-{{ $tone($f['hours_used_pct']) }}" style="width: {{ $bar($f['hours_used_pct']) }}%"></div>
                </div>
                <div class="small text-{{ $tone($f['hours_used_pct']) }} mt-1">{{ $f['hours_used_pct'] }}% of budgeted hours</div>
                @endif
                @if((float) $f['pending_hours'] > 0)
                <div class="small text-muted mt-1">{{ $hrs($f['pending_hours']) }} waiting for approval</div>
                @endif
            </div>

            <div class="col-md-{{ $canSeeMoney ? '4' : '6' }}">
                <div class="text-muted small mb-1">Work remaining (from task estimates)</div>
                <div class="fs-5 fw-bold">{{ $hrs($f['remaining_hours']) }}</div>
                <div class="small text-muted">
                    Projected total {{ $hrs($f['projected_hours']) }}
                    @if($f['budget_hours'] !== null && (float) $f['projected_hours'] > (float) $f['budget_hours'])
                    <span class="text-danger fw-medium">· over the hours budget</span>
                    @endif
                </div>
                @if($f['unestimated_open'] > 0)
                <div class="small text-warning mt-1">
                    <i class="fi fi-rr-triangle-warning"></i>
                    {{ $f['unestimated_open'] }} open {{ Str::plural('task', $f['unestimated_open']) }} without an estimate, so this is lower than reality.
                </div>
                @endif
            </div>

            {{-- Money: only for people allowed to see payroll figures --}}
            @if($canSeeMoney)
            <div class="col-md-4">
                <div class="text-muted small mb-1">Labour cost so far</div>
                <div class="fs-5 fw-bold">{{ $cur }}{{ $num($f['cost']) }}
                    @if($f['budget'] !== null)<span class="text-muted fs-6 fw-normal">of {{ $num($f['budget']) }}</span>@endif
                </div>
                @if($f['budget_used_pct'] !== null)
                <div class="progress mt-2" style="height:8px" role="progressbar" aria-label="Budget used" aria-valuenow="{{ $f['budget_used_pct'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-{{ $tone($f['budget_used_pct']) }}" style="width: {{ $bar($f['budget_used_pct']) }}%"></div>
                </div>
                <div class="small text-{{ $tone($f['budget_used_pct']) }} mt-1">{{ $f['budget_used_pct'] }}% of budget used</div>
                @endif
                @if($f['projected_cost'] !== null && $f['budget'] !== null)
                <div class="small mt-1 {{ $f['projected_pct'] > 100 ? 'text-danger fw-medium' : 'text-muted' }}">
                    Projected at completion: {{ $cur }}{{ $num($f['projected_cost']) }} ({{ $f['projected_pct'] }}%)
                </div>
                @endif
            </div>
            @endif
        </div>

        @if($canSeeMoney)
        <hr>
        <div class="row g-3 small">
            <div class="col-sm-4">
                <span class="text-muted">Billable value</span>
                <div class="fw-medium">{{ $cur }}{{ $num($f['billable_value']) }} <span class="text-muted">· {{ $hrs($f['billable_hours']) }} billable</span></div>
            </div>
            <div class="col-sm-4">
                <span class="text-muted">Margin (billable value − labour cost)</span>
                <div class="fw-medium {{ (float) $f['margin'] < 0 ? 'text-danger' : 'text-success' }}">{{ $cur }}{{ $num($f['margin']) }}</div>
            </div>
            <div class="col-sm-4 text-muted">
                @if((float) $f['uncosted_hours'] > 0)
                <div><i class="fi fi-rr-info"></i> {{ $hrs($f['uncosted_hours']) }} by people with no salary structure are not costed.</div>
                @endif
                @if((float) $f['unrated_hours'] > 0)
                <div><i class="fi fi-rr-info"></i> {{ $hrs($f['unrated_hours']) }} billable hours have no billing rate.</div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
