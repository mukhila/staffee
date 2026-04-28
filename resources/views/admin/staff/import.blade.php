<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Bulk Import Staff</h1>
            <span>Upload a CSV file to create multiple staff accounts at once</span>
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary btn-sm">Back to Staff</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Upload CSV</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.staff.import.upload') }}" enctype="multipart/form-data">
                        {{-- route: admin.staff.import.upload (inside admin group → full name: admin.staff.import.upload) --}}
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                            <input type="file" name="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".csv,.txt" required>
                            @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Default Role (if not in CSV)</label>
                            <select name="default_role" class="form-select @error('default_role') is-invalid @enderror" required>
                                @foreach($roles as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Default Department (if not in CSV)</label>
                            <select name="default_dept" class="form-select @error('default_dept') is-invalid @enderror" required>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Import Staff</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">CSV Format Guide</h6></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">First row must be a header row with these column names:</p>
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light"><tr><th>Column</th><th>Required</th><th>Notes</th></tr></thead>
                        <tbody>
                            <tr><td><code>name</code></td><td><span class="badge bg-danger">Yes</span></td><td>Full name</td></tr>
                            <tr><td><code>email</code></td><td><span class="badge bg-danger">Yes</span></td><td>Must be unique</td></tr>
                            <tr><td><code>password</code></td><td>No</td><td>Default: <code>Password@123</code></td></tr>
                            <tr><td><code>role</code></td><td>No</td><td>Role slug (uses default if blank)</td></tr>
                            <tr><td><code>department_id</code></td><td>No</td><td>Uses default if blank</td></tr>
                            <tr><td><code>phone</code></td><td>No</td><td>Phone number</td></tr>
                        </tbody>
                    </table>
                    <p class="small text-muted mb-1 mt-2">Example row:</p>
                    <code class="small d-block bg-light p-2 rounded">John Doe,john@company.com,Pass@1,staff,2,+91-9876543210</code>

                    <a href="#" class="btn btn-outline-secondary btn-sm mt-3"
                       onclick="downloadSample();return false;">Download Sample CSV</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadSample() {
    const csv = "name,email,password,role,department_id,phone\nJane Smith,jane@example.com,Pass@123,staff,1,+1-555-0100\nBob Jones,bob@example.com,,staff,,";
    const blob = new Blob([csv], {type: 'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'staff_import_sample.csv';
    a.click();
}
</script>
</x-app-layout>
