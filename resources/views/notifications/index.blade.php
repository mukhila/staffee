<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Notifications</h1>
                <span>All your notifications</span>
            </div>
            @if($notifications->total() > 0)
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary waves-effect">
                    <i class="fi fi-rr-check-double me-1"></i> Mark All as Read
                </button>
            </form>
            @endif
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body p-0">
                        @forelse($notifications as $notification)
                        <div class="d-flex align-items-start p-3 border-bottom {{ $notification->read_at ? '' : 'bg-primary bg-opacity-05' }}">
                            <div class="avatar avatar-sm rounded-circle me-3
                                @if($notification->type === 'task_assigned') bg-info
                                @elseif($notification->type === 'bug_assigned') bg-danger
                                @elseif($notification->type === 'leave_approved') bg-success
                                @elseif($notification->type === 'leave_rejected') bg-danger
                                @elseif($notification->type === 'leave_request') bg-warning
                                @else bg-secondary @endif
                                text-white">
                                <i class="fi
                                    @if($notification->type === 'task_assigned') fi-rr-list-check
                                    @elseif($notification->type === 'bug_assigned') fi-rr-bug
                                    @elseif(str_contains($notification->type, 'leave')) fi-rr-calendar
                                    @else fi-rr-bell @endif"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $notification->title }}</div>
                                <div class="text-muted small">{{ $notification->message }}</div>
                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            @if($notification->url)
                            <a href="{{ $notification->url }}" class="btn btn-sm btn-link">View</a>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <i class="fi fi-rr-bell-slash text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No notifications yet.</p>
                        </div>
                        @endforelse
                    </div>
                    @if($notifications->hasPages())
                    <div class="card-footer">
                        {{ $notifications->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
