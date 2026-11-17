<x-app-layout>
<style>
  .org-tree, .org-tree ul { list-style: none; margin: 0; padding-left: 0; }
  .org-tree ul { margin-left: 18px; padding-left: 18px; border-left: 2px solid var(--bs-border-color, #e3e6ec); }
  .org-tree li { position: relative; margin: 6px 0; }
  .org-tree ul > li::before { content: ""; position: absolute; left: -18px; top: 22px; width: 16px; border-top: 2px solid var(--bs-border-color, #e3e6ec); }
  .org-tree summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
  .org-tree summary::-webkit-details-marker { display: none; }
  .org-tree summary::before { content: "▸"; color: var(--bs-secondary-color, #6c757d); width: 12px; display: inline-block; transition: transform .15s; }
  .org-tree details[open] > summary::before { transform: rotate(90deg); }
  .org-tree .org-leaf { display: inline-flex; padding-left: 18px; }
  .org-card { display: inline-flex; align-items: center; gap: 10px; min-width: 260px; max-width: 360px; padding: 8px 12px; border: 1px solid var(--bs-border-color, #e3e6ec); border-radius: 10px; background: var(--bs-body-bg, #fff); }
  .org-card.org-focus { border-color: #316AFF; box-shadow: 0 0 0 3px rgba(49,106,255,.15); }
</style>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Org chart</h1>
            <span>{{ $focus ? $focus->name . "'s team" : 'Who reports to whom · ' . $total . ' people' }}</span>
        </div>
        <div class="d-flex gap-2">
            @if($focus)
            <a href="{{ route('people.org-chart') }}" class="btn btn-outline-secondary btn-sm">Whole company</a>
            @endif
            <a href="{{ route('people.directory') }}" class="btn btn-outline-primary btn-sm"><i class="fi fi-rr-users me-1"></i> Directory</a>
        </div>
    </div>

    @if($chain->isNotEmpty())
    <nav aria-label="Reporting line" class="mb-3">
        <ol class="breadcrumb small mb-0">
            @foreach($chain as $manager)
            <li class="breadcrumb-item"><a href="{{ route('people.org-chart', ['root' => $manager->id]) }}">{{ $manager->name }}</a></li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $focus->name }}</li>
        </ol>
    </nav>
    @endif

    <div class="card">
        <div class="card-body overflow-auto">
            @if($roots->isEmpty())
            <p class="text-muted text-center py-5 mb-0">No one to show yet. Set each person's manager on their staff record to build the chart.</p>
            @else
            <p class="small text-muted">Click a name to focus on that person's team. The number shows how many people are in their team in total.</p>
            <ul class="org-tree">
                @foreach($roots as $person)
                    @include('people._org-node', ['person' => $person, 'depth' => 0, 'path' => []])
                @endforeach
            </ul>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
