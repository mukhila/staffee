<x-app-layout>
@php
    $icons = [
        'departments'    => 'fi-rr-building',
        'staff'          => 'fi-rr-users',
        'holidays'       => 'fi-rr-calendar-star',
        'leave-balances' => 'fi-rr-calendar-minus',
        'attendance'     => 'fi-rr-calendar-clock',
    ];
@endphp
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Import Data</h1>
            <span>Bring records in from a spreadsheet. Every file is checked first, and nothing is saved until you confirm.</span>
        </div>
    </div>

    <div class="alert alert-info small">
        <i class="fi fi-rr-info me-1"></i>
        Moving from another system? Import in this order: departments, then staff, then holidays, leave balances and attendance.
    </div>

    <div class="row g-3">
        @foreach($types as $key => $type)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary-subtle" style="width:40px;height:40px;">
                            <i class="fi {{ $icons[$key] ?? 'fi-rr-file-csv' }} text-primary"></i>
                        </div>
                        <h6 class="mb-0">{{ $type->label() }}</h6>
                    </div>
                    <p class="small text-muted flex-grow-1">{{ $type->description() }}</p>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.imports.show', $key) }}" class="btn btn-primary btn-sm flex-fill">Import</a>
                        <a href="{{ route('admin.imports.template', $key) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fi fi-rr-download me-1"></i> Template
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
</x-app-layout>
