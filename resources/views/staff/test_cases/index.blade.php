<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Test Cases</h1>
            <span>Manage your test cases</span>
          </div>
          <a href="{{ route('staff.test-cases.create') }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-plus me-1"></i> Add Test Case
          </a>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Test Case List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_TestCases" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">Title</th>
                    <th class="minw-150px">Project</th>
                    <th class="minw-100px">Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($testCases as $testCase)
                  <tr>
                    <td>{{ $testCase->title }}</td>
                    <td>{{ $testCase->project->name }}</td>
                    <td>
                        @if($testCase->status == 'pass')
                            <span class="badge bg-success">Pass</span>
                        @elseif($testCase->status == 'fail')
                            <span class="badge bg-danger">Fail</span>
                        @elseif($testCase->status == 'need_to_test')
                            <span class="badge bg-info">Need to Test</span>
                        @else
                            <span class="badge bg-warning">Pending</span>
                        @endif
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                        <ul class="dropdown-menu" style="">
                            <li><a class="dropdown-item" href="{{ route('staff.test-cases.edit', $testCase->id) }}">Edit</a></li>
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
