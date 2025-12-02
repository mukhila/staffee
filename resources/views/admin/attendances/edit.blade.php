<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Edit Attendance</h1>
            <span>Update attendance record</span>
          </div>
          <a href="{{ route('admin.attendances.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.attendances.update', $attendance->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Staff Name</label>
                        <input type="text" class="form-control" value="{{ $attendance->user->name }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" value="{{ $attendance->date }}" disabled>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="check_in" class="form-label">Check In</label>
                            <input type="time" class="form-control" id="check_in" name="check_in" value="{{ $attendance->check_in }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="check_out" class="form-label">Check Out</label>
                            <input type="time" class="form-control" id="check_out" name="check_out" value="{{ $attendance->check_out }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="present" {{ $attendance->status == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="absent" {{ $attendance->status == 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="leave" {{ $attendance->status == 'leave' ? 'selected' : '' }}>Leave</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Attendance</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
