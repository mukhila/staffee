<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Import {{ $type->label() }}</h1>
            <span>{{ $type->description() }}</span>
        </div>
        <a href="{{ route('admin.imports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> All imports
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">1. Upload your file</h6></div>
                <div class="card-body">
                    <ol class="small ps-3">
                        <li><a href="{{ route('admin.imports.template', $type->key()) }}">Download the template</a> and fill it in, one row per record.</li>
                        <li>Save it as CSV (Excel: File → Save As → CSV).</li>
                        <li>Upload it below. You will see what will happen before anything is saved.</li>
                    </ol>
                    <form method="POST" action="{{ route('admin.imports.preview', $type->key()) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="form-label" for="file">CSV file</label>
                        <input type="file" id="file" name="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text mb-3">Up to 2 MB and {{ number_format($maxRows) }} rows.</div>
                        <button type="submit" class="btn btn-primary w-100">Check file</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Columns</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light"><tr><th>Column</th><th></th><th>What to put</th><th>Example</th></tr></thead>
                        <tbody>
                            @foreach($type->columns() as $name => $column)
                            <tr>
                                <td class="font-monospace fw-medium">{{ $name }}</td>
                                <td>
                                    @if($column['required'])
                                    <span class="badge bg-danger-subtle text-danger">Required</span>
                                    @else
                                    <span class="badge bg-secondary-subtle text-secondary">Optional</span>
                                    @endif
                                </td>
                                <td>{{ $column['help'] }}</td>
                                <td class="text-muted">{{ $column['example'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">Column names are not case-sensitive and can be in any order. Extra columns are ignored.</div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
