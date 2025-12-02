<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Roles</h1>
            <span>Manage roles</span>
          </div>
          <a href="{{ route('admin.roles.create') }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-plus me-1"></i> Add Role
          </a>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Role List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_Roles" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">Name</th>
                    <th class="minw-150px">Slug</th>
                    <th class="minw-200px">Description</th>
                    <th class="minw-150px">Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                  <tr>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->slug }}</td>
                    <td>{{ $role->description }}</td>
                    <td>
                        @if($role->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                        <ul class="dropdown-menu" style="">
                            <li><a class="dropdown-item" href="{{ route('admin.roles.edit', $role->id) }}">Edit</a></li><li> <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">Delete</button>
                            </form></li></ul>
                        </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
        </div>
    </div>
</x-app-layout>
