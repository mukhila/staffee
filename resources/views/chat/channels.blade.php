<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Group Channels</h1>
            <span>Department and project group chats</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary btn-sm">Direct Messages</a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createChannelModal">
                <i class="fi fi-rr-plus me-1"></i> New Channel
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        @forelse($channels as $channel)
        <div class="col-md-4">
            <a href="{{ route('chat.channels.show', $channel) }}" class="text-decoration-none">
                <div class="card h-100 hover-shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:40px;height:40px;background:{{ $channel->type === 'department' ? '#0d6efd20' : ($channel->type === 'project' ? '#19875420' : '#6c757d20') }}">
                                <i class="fi fi-rr-{{ $channel->type === 'department' ? 'building' : ($channel->type === 'project' ? 'briefcase' : 'comments') }}
                                   text-{{ $channel->type === 'department' ? 'primary' : ($channel->type === 'project' ? 'success' : 'secondary') }} small"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-1">
                                    <div class="fw-bold text-dark text-truncate"># {{ $channel->name }}</div>
                                    @php $unread = $channel->unreadCount(auth()->id()); @endphp
                                    @if($unread > 0)
                                    <span class="badge bg-danger rounded-pill ms-1">{{ $unread }}</span>
                                    @endif
                                </div>
                                <div class="text-muted small">{{ ucfirst($channel->type) }} · {{ $channel->members->count() }} members</div>
                            </div>
                        </div>
                        @if($channel->latestMessage)
                        <div class="text-muted small text-truncate">
                            <strong>{{ $channel->latestMessage->user?->name }}:</strong>
                            {{ Str::limit($channel->latestMessage->body ?? '[attachment]', 50) }}
                        </div>
                        <div class="text-muted" style="font-size:.7rem;">{{ $channel->latestMessage->created_at->diffForHumans() }}</div>
                        @elseif($channel->description)
                        <div class="text-muted small">{{ Str::limit($channel->description, 60) }}</div>
                        @else
                        <div class="text-muted small fst-italic">No messages yet</div>
                        @endif
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="fi fi-rr-comments fs-2 d-block mb-2 opacity-25"></i>
                No channels yet. Create one to get started.
            </div>
        </div>
        @endforelse
    </div>
</div>

{{-- Create Channel Modal --}}
<div class="modal fade" id="createChannelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('chat.channels.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">New Channel</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Channel Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. design-team">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" id="channelTypeSelect" onchange="toggleReference(this.value)">
                            <option value="general">General</option>
                            <option value="department">Department</option>
                            <option value="project">Project</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="deptRef">
                        <label class="form-label">Department</label>
                        <select name="reference_id" class="form-select">
                            <option value="">— Select —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="projRef">
                        <label class="form-label">Project</label>
                        <select name="reference_id" class="form-select">
                            <option value="">— Select —</option>
                            @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Optional description">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Add Members</label>
                        <select name="members[]" class="form-select" multiple size="4">
                            @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create Channel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleReference(type) {
    document.getElementById('deptRef').classList.toggle('d-none', type !== 'department');
    document.getElementById('projRef').classList.toggle('d-none', type !== 'project');
}
</script>
</x-app-layout>
