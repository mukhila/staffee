<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Add Role</h1>
            <span>Create a new role</span>
          </div>
          <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" required>
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">Select Department (Optional)</option>
                            @foreach(\App\Models\Department::where('is_active', true)->get() as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="row">
                            @php
                                $groupedPermissions = $permissions->groupBy(function($item) {
                                    return explode('.', $item->slug)[0];
                                });
                            @endphp
                            @foreach($groupedPermissions as $module => $perms)
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header py-2">
                                            <h6 class="mb-0 text-capitalize">{{ str_replace('_', ' ', $module) }}</h6>
                                        </div>
                                        <div class="card-body py-2">
                                            @foreach($perms as $permission)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $permission->id }}">
                                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
