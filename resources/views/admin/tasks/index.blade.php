<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Tasks</h1>
            <span>Manage tasks</span>
          </div>
          <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-plus me-1"></i> Add Task
          </a>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Task List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_Tasks" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">Title</th>
                    <th class="minw-150px">Project</th>
                    <th class="minw-150px">Assigned To</th>
                    <th class="minw-100px">Status</th>
                    <th class="minw-100px">Due Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                  <tr>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->project->name }}</td>
                    <td>{{ $task->assignedUser->name }}</td>
                    <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
                    <td>{{ $task->due_date }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                        <ul class="dropdown-menu" style="">
                            <li><a class="dropdown-item" href="{{ route('admin.tasks.edit', $task->id) }}">Edit</a></li>
                            <li>
                                <form action="{{ route('admin.tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
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
