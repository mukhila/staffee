<x-app-layout>
@php $errorCount = count($result['errors']); @endphp
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Check {{ $type->label() }} import</h1>
            <span>{{ $filename }} · nothing has been saved yet</span>
        </div>
        <a href="{{ route('admin.imports.show', $type->key()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Upload a different file
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Rows in file</div>
                <div class="fs-4 fw-bold">{{ number_format($result['total']) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-success"><div class="card-body">
                <div class="text-muted small">Ready to import</div>
                <div class="fs-4 fw-bold text-success">{{ number_format($result['valid']) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 {{ $errorCount ? 'border-danger' : '' }}"><div class="card-body">
                <div class="text-muted small">Rows with problems</div>
                <div class="fs-4 fw-bold {{ $errorCount ? 'text-danger' : '' }}">{{ number_format($errorCount) }}</div>
            </div></div>
        </div>
    </div>

    @if($result['ignored_columns'])
    <div class="alert alert-secondary small">
        These columns are not used and will be ignored: <strong>{{ implode(', ', $result['ignored_columns']) }}</strong>.
        If one is a typo of an expected column, fix the header and upload again.
    </div>
    @endif

    {{-- Confirm --}}
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
            @if($result['token'])
            <div>
                <strong>2. Import {{ number_format($result['valid']) }} {{ Str::plural('row', $result['valid']) }}?</strong>
                @if($errorCount)
                <div class="small text-muted">The {{ $errorCount }} {{ Str::plural('row', $errorCount) }} with problems will be left out. You can fix them and import them later.</div>
                @endif
                <div class="small text-muted">This preview can be confirmed for the next {{ \App\Services\Import\CsvImportService::PREVIEW_MINUTES }} minutes.</div>
            </div>
            <form method="POST" action="{{ route('admin.imports.confirm', $type->key()) }}">
                @csrf
                <input type="hidden" name="token" value="{{ $result['token'] }}">
                <button type="submit" class="btn btn-success"><i class="fi fi-rr-check me-1"></i> Import {{ number_format($result['valid']) }} {{ Str::plural('row', $result['valid']) }}</button>
            </form>
            @else
            <div><strong>No rows can be imported.</strong> <span class="small text-muted">Fix the problems below and upload the file again.</span></div>
            @endif
        </div>
    </div>

    @if($errorCount)
    <div class="card mb-3 border-danger">
        <div class="card-header"><h6 class="mb-0 text-danger">Rows with problems</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 small">
                    <thead class="table-light"><tr><th style="width:70px">Line</th><th>Problem</th><th>Row</th></tr></thead>
                    <tbody>
                        @foreach(array_slice($result['errors'], 0, 200) as $error)
                        <tr>
                            <td class="fw-medium">{{ $error['line'] }}</td>
                            <td class="text-danger">{{ $error['message'] }}</td>
                            <td class="text-muted font-monospace">{{ Str::limit(implode(', ', $error['values']), 90) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if($errorCount > 200)
        <div class="card-footer small text-muted">Showing the first 200 of {{ $errorCount }} problems.</div>
        @endif
    </div>
    @endif

    @if($result['sample'])
    <div class="card">
        <div class="card-header"><h6 class="mb-0">First rows of your file</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 small">
                    <thead class="table-light">
                        <tr>@foreach(array_keys($result['sample'][0]) as $column)<th class="font-monospace">{{ $column }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach($result['sample'] as $row)
                        <tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
</x-app-layout>
