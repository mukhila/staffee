<x-app-layout>
    <div class="container-fluid">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Kanban Board</h1>
            <span>Manage tasks visually</span>
          </div>
          <a href="{{ route('staff.tasks.index') }}" class="btn btn-secondary waves-effect waves-light">
            List View
          </a>
        </div>

        <div class="row flex-nowrap overflow-auto pb-4" style="min-height: 70vh;">
            @foreach(['pending', 'in_progress', 'review', 'completed'] as $status)
                <div class="col-md-3 col-sm-6" style="min-width: 300px;">
                    <div class="card h-100 bg-light">
                        <div class="card-header py-2 bg-white border-bottom">
                            <h6 class="mb-0 text-capitalize d-flex justify-content-between align-items-center">
                                {{ str_replace('_', ' ', $status) }}
                                <span class="badge bg-secondary rounded-pill">{{ $tasks->where('status', $status)->count() }}</span>
                            </h6>
                        </div>
                        <div class="card-body p-2 kanban-column" data-status="{{ $status }}" id="kanban-{{ $status }}">
                            @foreach($tasks->where('status', $status) as $task)
                                <div class="card mb-2 shadow-sm kanban-item cursor-move" data-id="{{ $task->id }}">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-1">{{ $task->title }}</h6>
                                        <p class="card-text text-muted small mb-2">{{ Str::limit($task->description, 50) }}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-primary bg-opacity-10 text-primary">{{ $task->project->name }}</span>
                                            <small class="text-muted">{{ $task->due_date }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const columns = document.querySelectorAll('.kanban-column');
            
            columns.forEach(column => {
                new Sortable(column, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'bg-light',
                    onEnd: function (evt) {
                        const itemEl = evt.item;
                        const newStatus = evt.to.getAttribute('data-status');
                        const taskId = itemEl.getAttribute('data-id');
                        
                        // Send AJAX request to update status
                        fetch(`/staff/kanban/update-status/${taskId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Optional: Show toast notification
                                console.log('Status updated');
                            } else {
                                alert('Failed to update status');
                                evt.from.appendChild(itemEl); // Revert move
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred');
                            evt.from.appendChild(itemEl); // Revert move
                        });
                    }
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
