<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">My Tasks</h1>
            <span>Manage your assigned tasks</span>
          </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Task List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_MyTasks" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">Title</th>
                    <th class="minw-150px">Project</th>
                    <th class="minw-100px">Due Date</th>
                    <th class="minw-100px">Timer</th>
                    <th class="minw-150px">Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                  <tr>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->project->name }}</td>
                    <td>{{ $task->due_date }}</td>
                    <td>
                        @if($task->status == 'pending' || $task->status == 'in_progress')
                            @php
                                $activeTimer = \App\Models\TimeTracker::where('user_id', auth()->id())
                                    ->where('trackable_type', \App\Models\Task::class)
                                    ->where('trackable_id', $task->id)
                                    ->whereNull('end_time')
                                    ->first();
                            @endphp

                            @if($activeTimer)
                                <button type="button" class="btn btn-sm btn-danger stop-timer-btn" data-id="{{ $task->id }}" data-type="task" data-bs-toggle="modal" data-bs-target="#stopTimerModal">
                                    <i class="fi fi-rr-stop"></i> Stop
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-success start-timer-btn" data-id="{{ $task->id }}" data-type="task">
                                    <i class="fi fi-rr-play"></i> Start
                                </button>
                            @endif
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('staff.tasks.update', $task->id) }}" method="POST" class="d-flex gap-2">
                            @csrf
                            @method('PUT')
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="review" {{ $task->status == 'review' ? 'selected' : '' }}>Review</option>
                                <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <!-- Add view details modal or link if needed -->
                        <div class="btn-group" role="group">
                            <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                            <ul class="dropdown-menu" style="">
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#taskModal{{ $task->id }}">View</a>
                                </li>
                            </ul>
                        </div>

                        <!-- Modal -->
                        <div class="modal fade" id="taskModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ $task->title }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Project:</strong> {{ $task->project->name }}</p>
                                        <p><strong>Description:</strong></p>
                                        <p>{{ $task->description }}</p>
                                        <p><strong>Due Date:</strong> {{ $task->due_date }}</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Stop Timer Modal -->
    <div class="modal fade" id="stopTimerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stop Timer & Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="stopTimerForm">
                        <div class="mb-3">
                            <label for="timerDescription" class="form-label">Description (What did you do?) <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="timerDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="timerCategory" class="form-label">Category</label>
                            <select class="form-select" id="timerCategory">
                                <option value="">— Loading categories… —</option>
                            </select>
                            <div class="form-text" id="categoryBillableHint"></div>
                        </div>
                        <div class="mb-3">
                            <label for="timerNotes" class="form-label">Notes</label>
                            <input type="text" class="form-control" id="timerNotes" maxlength="500" placeholder="Optional internal notes">
                        </div>
                        <div class="mb-3">
                            <label for="timerStatus" class="form-label">Update Status</label>
                            <select class="form-select" id="timerStatus" required>
                                <option value="in_progress">In Progress</option>
                                <option value="review">Review</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmStopTimer">Save & Stop</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '{{ csrf_token() }}';
            const loginUrl  = '{{ route("login") }}';

            function handleUnauth(response) {
                if (response.status === 401 || response.status === 419) {
                    window.location.href = loginUrl;
                    return true;
                }
                return false;
            }

            // Load categories into the stop modal when it opens
            const stopModal = document.getElementById('stopTimerModal');
            stopModal.addEventListener('show.bs.modal', function() {
                fetch('{{ route("time-tracker.categories") }}', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                })
                .then(r => r.json())
                .then(categories => {
                    const sel = document.getElementById('timerCategory');
                    sel.innerHTML = '<option value="">— No category —</option>';
                    categories.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name + (c.is_billable ? ' (Billable)' : ' (Non-billable)');
                        opt.dataset.billable = c.is_billable ? '1' : '0';
                        sel.appendChild(opt);
                    });
                })
                .catch(() => {
                    document.getElementById('timerCategory').innerHTML = '<option value="">— Categories unavailable —</option>';
                });
            });

            // Show billable hint when category changes
            document.getElementById('timerCategory').addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                const hint = document.getElementById('categoryBillableHint');
                if (opt && opt.dataset.billable === '1') {
                    hint.textContent = 'This entry will be counted as billable.';
                    hint.className = 'form-text text-success';
                } else if (opt && opt.value) {
                    hint.textContent = 'This entry will be counted as non-billable.';
                    hint.className = 'form-text text-muted';
                } else {
                    hint.textContent = '';
                }
            });

            // Start Timer
            document.querySelectorAll('.start-timer-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id   = this.dataset.id;
                    const type = this.dataset.type;

                    fetch('{{ route("time-tracker.start") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ id, type })
                    })
                    .then(response => { if (handleUnauth(response)) return; return response.json(); })
                    .then(data => {
                        if (!data) return;
                        if (data.success) { location.reload(); }
                        else { alert(data.error || 'Error starting timer'); }
                    });
                });
            });

            // Stop Timer
            document.getElementById('confirmStopTimer').addEventListener('click', function() {
                const description = document.getElementById('timerDescription').value.trim();
                const status      = document.getElementById('timerStatus').value;
                const category_id = document.getElementById('timerCategory').value || null;
                const notes       = document.getElementById('timerNotes').value.trim() || null;

                if (!description) {
                    alert('Please enter a description');
                    return;
                }

                fetch('{{ route("time-tracker.stop") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ description, status, category_id, notes })
                })
                .then(response => { if (handleUnauth(response)) return; return response.json(); })
                .then(data => {
                    if (!data) return;
                    if (data.success) { location.reload(); }
                    else { alert(data.error || 'Error stopping timer'); }
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
