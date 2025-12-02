<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Staff Management</h1>
            <span>Manage your staff members</span>
          </div>
          <a href="{{ route('admin.staff.create') }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-plus me-1"></i> Add Staff
          </a>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Staff List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_Staff" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">Name</th>
                    <th class="minw-200px">Email</th>
                    <th class="minw-150px">Role</th>
                    <th class="minw-150px">Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($staff as $user)
                  <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($user->role) }}</span></td>
                    <td>
                        @if($user->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                        <ul class="dropdown-menu" style="">
                            <li><a class="dropdown-item" href="{{ route('admin.staff.edit', $user->id) }}">Edit</a></li>
                            <li>
                                <form action="{{ route('admin.staff.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                </form>
                            </li>
                        </ul>
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
