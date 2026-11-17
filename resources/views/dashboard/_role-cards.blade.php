{{-- ── Needs your attention (anyone who approves things) ────────────── --}}
@if(($pendingActions ?? collect())->isNotEmpty())
<div class="card mb-3 border-primary-subtle">
  <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
    <h6 class="card-title mb-0"><i class="fi fi-rr-bell me-1 text-primary"></i> Needs your attention</h6>
    <span class="badge bg-primary">{{ $pendingActions->sum('count') }}</span>
  </div>
  <div class="card-body">
    <div class="row g-2">
      @foreach($pendingActions as $item)
      <div class="col-6 col-md-4 col-xl-3">
        <a href="{{ $item['url'] }}" class="d-flex align-items-center gap-2 p-2 rounded border text-decoration-none h-100">
          <i class="fi {{ $item['icon'] }} text-primary"></i>
          <span class="small text-body flex-grow-1">{{ $item['label'] }}</span>
          <span class="badge bg-primary-subtle text-primary">{{ $item['count'] }}</span>
        </a>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- ── Company today (admins) ──────────────────────────────────────── --}}
@if($companyToday ?? null)
<div class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-4 align-items-center">
    <h6 class="mb-0 me-2">Today</h6>
    <div><span class="fs-5 fw-bold text-success">{{ $companyToday['in'] }}</span> <span class="small text-muted">checked in</span></div>
    <div><span class="fs-5 fw-bold text-warning">{{ $companyToday['on_leave'] }}</span> <span class="small text-muted">on leave</span></div>
    <div><span class="fs-5 fw-bold text-secondary">{{ $companyToday['not_in'] }}</span> <span class="small text-muted">not in yet</span></div>
    <div class="small text-muted ms-auto">of {{ $companyToday['total'] }} staff</div>
    @if($companyToday['total'] > 0)
    <div class="progress w-100" style="height: 8px" role="img"
         aria-label="{{ $companyToday['in'] }} checked in, {{ $companyToday['on_leave'] }} on leave, {{ $companyToday['not_in'] }} not in yet">
      <div class="progress-bar bg-success" style="width: {{ $companyToday['in'] / $companyToday['total'] * 100 }}%"></div>
      <div class="progress-bar bg-warning" style="width: {{ $companyToday['on_leave'] / $companyToday['total'] * 100 }}%"></div>
    </div>
    @endif
  </div>
</div>
@endif

{{-- ── My team today (managers) ────────────────────────────────────── --}}
@if(($teamToday ?? collect())->isNotEmpty())
@php $stateColors = ['in' => 'success', 'done' => 'secondary', 'not_in' => 'warning', 'leave' => 'info']; @endphp
<div class="card mb-3">
  <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
    <h6 class="card-title mb-0">My team today</h6>
    <span class="small text-muted">
      {{ $teamToday->whereIn('state', ['in', 'done'])->count() }} in ·
      {{ $teamToday->where('state', 'leave')->count() }} on leave ·
      {{ $teamToday->where('state', 'not_in')->count() }} not in yet
    </span>
  </div>
  <div class="card-body">
    <div class="row g-2">
      @foreach($teamToday as $row)
      <div class="col-sm-6 col-lg-4">
        <div class="d-flex align-items-center gap-2 p-2 border rounded">
          @include('people._avatar', ['person' => $row['user'], 'size' => 32])
          <div class="min-w-0 flex-grow-1">
            <div class="small fw-medium text-truncate">{{ $row['user']->name }}</div>
            <div class="text-muted text-truncate" style="font-size:.72rem">{{ $row['time'] ?? ($row['user']->designation ?: '') }}</div>
          </div>
          <span class="badge bg-{{ $stateColors[$row['state']] }}-subtle text-{{ $stateColors[$row['state']] }}">{{ $row['label'] }}</span>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif
