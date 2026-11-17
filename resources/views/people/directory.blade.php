<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">People</h1>
            <span>Find a colleague, see who they work with, and celebrate milestones</span>
        </div>
        <a href="{{ route('people.org-chart') }}" class="btn btn-outline-primary btn-sm">
            <i class="fi fi-rr-sitemap me-1"></i> Org chart
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-9">
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end" role="search">
                        <div class="col-md-7">
                            <label class="form-label small mb-1" for="q">Search</label>
                            <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Name, job title, email or department" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1" for="department_id">Department</label>
                            <select id="department_id" name="department_id" class="form-select form-select-sm">
                                <option value="">All</option>
                                @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ ($filters['department_id'] ?? null) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-sm btn-primary flex-fill">Search</button>
                            @if(array_filter($filters))
                            <a href="{{ route('people.directory') }}" class="btn btn-sm btn-outline-secondary" aria-label="Clear search">✕</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <p class="small text-muted mb-2">{{ $people->total() }} {{ Str::plural('person', $people->total()) }}</p>

            <div class="row g-3">
                @forelse($people as $person)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex gap-3 align-items-center mb-2">
                                @include('people._avatar', ['person' => $person, 'size' => 48])
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $person->name }}</div>
                                    <div class="small text-muted text-truncate">{{ $person->designation ?: ucfirst($person->role) }}</div>
                                </div>
                            </div>
                            <ul class="list-unstyled small mb-0">
                                <li class="text-truncate"><i class="fi fi-rr-building text-muted me-1"></i>{{ $person->department?->name ?? 'No department' }}</li>
                                <li class="text-truncate"><i class="fi fi-rr-envelope text-muted me-1"></i><a href="mailto:{{ $person->email }}">{{ $person->email }}</a></li>
                                @if($showPhone && $person->phone)
                                <li><i class="fi fi-rr-phone-call text-muted me-1"></i>{{ $person->phone }}</li>
                                @endif
                                @if($person->manager)
                                <li class="text-truncate"><i class="fi fi-rr-user text-muted me-1"></i>Reports to {{ $person->manager->name }}</li>
                                @endif
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent d-flex gap-2">
                            @if($person->id !== auth()->id())
                            <a href="{{ route('chat.index', ['user' => $person->id]) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="fi fi-rr-comment-alt me-1"></i> Message
                            </a>
                            @endif
                            <a href="{{ route('people.org-chart', ['root' => $person->id]) }}" class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="fi fi-rr-sitemap me-1"></i> Team
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-center text-muted py-5">No one matches that search.</div></div>
                </div>
                @endforelse
            </div>

            @if($people->hasPages())
            <div class="mt-3">{{ $people->links() }}</div>
            @endif
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Next 30 days</h6></div>
                @include('people._celebrations', ['celebrations' => $celebrations])
            </div>
        </div>
    </div>
</div>
</x-app-layout>
