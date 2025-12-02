<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Projects</h1>
            <span>Manage your projects</span>
          </div>
          <a href="{{ route('admin.projects.create') }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-plus me-1"></i> Add Project
          </a>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Project List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_Projects" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">Name</th>
                    <th class="minw-100px">Start Date</th>
                    <th class="minw-100px">End Date</th>
                    <th class="minw-100px">Status</th>
                    <th class="minw-150px">Team</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                  <tr>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->start_date }}</td>
                    <td>{{ $project->end_date }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($project->status) }}</span></td>
                    <td>
                        @foreach($project->users as $user)
                            <span class="badge bg-secondary">{{ $user->name }}</span>
                        @endforeach
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                        <ul class="dropdown-menu" style="">
                            <li><a class="dropdown-item" href="{{ route('admin.projects.edit', $project->id) }}">Edit</a></li>
                            <li>
                                <form action="{{ route('admin.projects.destroy', $project->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
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
