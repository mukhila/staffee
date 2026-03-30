<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Add Staff</h1>
            <span>Create a new staff member</span>
          </div>
          <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.staff.store') }}" method="POST">
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
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Department First</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reporting_to" class="form-label">Reporting Leader</label>
                        <select class="form-select" id="reporting_to" name="reporting_to">
                            <option value="">Select Leader (Optional)</option>
                            @foreach(\App\Models\User::where('role', '!=', 'staff')->get() as $leader)
                                <option value="{{ $leader->id }}">{{ $leader->name }} ({{ $leader->role }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Staff</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('department_id').addEventListener('change', function() {
            var departmentId = this.value;
            var roleSelect = document.getElementById('role');
            roleSelect.innerHTML = '<option value="">Loading...</option>';

            if (departmentId) {
                fetch('/admin/api/roles?department_id=' + departmentId)
                    .then(response => response.json())
                    .then(data => {
                        roleSelect.innerHTML = '<option value="">Select Role</option>';
                        data.forEach(role => {
                            roleSelect.innerHTML += '<option value="' + role.slug + '">' + role.name + '</option>';
                        });
                    });
            } else {
                roleSelect.innerHTML = '<option value="">Select Department First</option>';
            }
        });
    </script>
</x-app-layout>
