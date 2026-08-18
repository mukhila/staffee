<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">ESS Portal</h1>
                <span>Welcome back, {{ $user->name }}</span>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-calendar-minus fs-4 text-warning"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $pendingLeaves }}</div>
                            <div class="text-muted small">Pending Leaves</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-calendar-clock fs-4 text-success"></i>
                        </div>
                        <div>
                            @if($todayAttendance)
                                <div class="fs-6 fw-bold">
                                    {{ $todayAttendance->check_in ? 'In: '.\Carbon\Carbon::parse($todayAttendance->check_in)->format('H:i') : 'Not checked in' }}
                                </div>
                                <div class="small text-muted">
                                    {{ $todayAttendance->check_out ? 'Out: '.\Carbon\Carbon::parse($todayAttendance->check_out)->format('H:i') : 'Still active' }}
                                </div>
                            @else
                                <div class="fs-6 fw-bold text-danger">Not checked in</div>
                                <div class="small text-muted">Today</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-list-check fs-4 text-info"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $currentTasksCount }}</div>
                            <div class="text-muted small">Active Tasks</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-money fs-4 text-primary"></i>
                        </div>
                        <div>
                            @if($upcomingPayslip)
                                <div class="fs-6 fw-bold">{{ $upcomingPayslip->period_start->format('M Y') }}</div>
                                <div class="small text-muted">Latest Payslip</div>
                            @else
                                <div class="fs-6 fw-bold text-muted">No payslips yet</div>
                                <div class="small text-muted">Payslips</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Left column --}}
            <div class="col-lg-8">
                {{-- Announcements --}}
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h6 class="mb-0"><i class="fi fi-rr-megaphone me-2"></i>Recent Announcements</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($announcements as $ann)
                        <div class="px-3 py-2 border-bottom">
                            <div class="fw-semibold">{{ $ann->title }}</div>
                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($ann->body, 120) }}</div>
                            <div class="text-muted small mt-1">{{ $ann->created_at->diffForHumans() }}</div>
                        </div>
                        @empty
                        <div class="px-3 py-4 text-muted text-center">No announcements</div>
                        @endforelse
                    </div>
                </div>

                {{-- My Team --}}
                @if($teammates->isNotEmpty())
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fi fi-rr-users me-2"></i>My Team — {{ $user->department?->name }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($teammates as $mate)
                            <div class="d-flex align-items-center gap-2">
                                @if($mate->avatar_url)
                                    <img src="{{ $mate->avatar_url }}" class="rounded-circle" width="32" height="32" alt="">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:12px;">
                                        {{ $mate->avatar_initials }}
                                    </div>
                                @endif
                                <span class="small">{{ $mate->name }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Right column — Action items --}}
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fi fi-rr-bell me-2"></i>Pending Actions</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($pendingLeaveRequests->isNotEmpty())
                        <div class="px-3 pt-2 pb-1">
                            <div class="small fw-semibold text-muted text-uppercase mb-1">Leaves Awaiting</div>
                            @foreach($pendingLeaveRequests as $lr)
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span class="small">{{ $lr->type_label }}</span>
                                <span class="badge bg-{{ $lr->status_color }}">{{ $lr->status_label }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($pendingExpenses->isNotEmpty())
                        <div class="px-3 pt-2 pb-1">
                            <div class="small fw-semibold text-muted text-uppercase mb-1">Expense Claims</div>
                            @foreach($pendingExpenses as $exp)
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <span class="small">{{ \Illuminate\Support\Str::limit($exp->title, 30) }}</span>
                                <span class="badge bg-warning">{{ ucfirst($exp->status) }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($pendingLeaveRequests->isEmpty() && $pendingExpenses->isEmpty())
                        <div class="px-3 py-4 text-center text-muted small">No pending actions</div>
                        @endif
                    </div>
                </div>

                {{-- Quick links --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fi fi-rr-link me-2"></i>Quick Links</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('staff.leaves.create') }}" class="list-group-item list-group-item-action">
                            <i class="fi fi-rr-calendar-plus me-2"></i> Apply for Leave
                        </a>
                        <a href="{{ route('staff.document-requests.create') }}" class="list-group-item list-group-item-action">
                            <i class="fi fi-rr-file-copy me-2"></i> Request Document
                        </a>
                        <a href="{{ route('staff.suggestions.create') }}" class="list-group-item list-group-item-action">
                            <i class="fi fi-rr-comment-dots me-2"></i> Submit Suggestion
                        </a>
                        <a href="{{ route('staff.payslips.index') }}" class="list-group-item list-group-item-action">
                            <i class="fi fi-rr-money me-2"></i> My Payslips
                        </a>
                        <a href="{{ route('staff.profile.index') }}" class="list-group-item list-group-item-action">
                            <i class="fi fi-rr-id-card-clip-alt me-2"></i> My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
