<x-app-layout>
    <div class="container-fluid">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Permission Matrix</h1>
                <span>Manage role permissions across the system</span>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary waves-effect waves-light">
                <i class="fi fi-rr-arrow-left me-1"></i> Back to Roles
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('admin.roles.matrix.update') }}" method="POST">
            @csrf

            @foreach($permissions as $category => $perms)
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                        <h6 class="card-title mb-0">
                            <i class="fi fi-rr-shield-check me-2 text-primary"></i>
                            {{ $categories[$category] ?? ucwords(str_replace('_', ' ', $category)) }}
                        </h6>
                        <small class="text-muted">{{ $perms->count() }} permissions</small>
                    </div>
                    <div class="card-body px-0 pb-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:220px;">Permission</th>
                                        <th style="min-width:160px;" class="text-muted small">Description</th>
                                        @foreach($roles as $role)
                                            <th class="text-center" style="min-width:110px;">
                                                <span class="badge bg-primary-subtle text-primary fw-semibold">{{ $role->name }}</span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($perms as $permission)
                                        <tr>
                                            <td>
                                                <span class="fw-medium">{{ $permission->name }}</span>
                                                <br><code class="text-muted" style="font-size:.75rem;">{{ $permission->slug }}</code>
                                            </td>
                                            <td class="text-muted small">{{ $permission->description }}</td>
                                            @foreach($roles as $role)
                                                <td class="text-center">
                                                    <div class="form-check d-flex justify-content-center m-0">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               name="permissions[{{ $role->id }}][]"
                                                               value="{{ $permission->id }}"
                                                               {{ isset($matrix[$role->id][$permission->id]) ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary waves-effect waves-light">
                    <i class="fi fi-rr-disk me-1"></i> Save Matrix
                </button>
            </div>
        </form>
    </div>

    <script>
        // Toggle all checkboxes in a role column (double-click the role header)
        document.querySelectorAll('thead th').forEach(th => {
            th.addEventListener('dblclick', function () {
                const colIdx = [...this.parentElement.children].indexOf(this);
                if (colIdx < 2) return; // skip Permission + Description columns
                this.closest('table').querySelectorAll('tbody tr').forEach(row => {
                    const cb = row.children[colIdx]?.querySelector('input[type=checkbox]');
                    if (cb) cb.checked = !cb.checked;
                });
            });
        });
    </script>
</x-app-layout>
