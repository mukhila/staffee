<x-app-layout>
    <div class="container">
        <div class="app-page-head mb-4">
            <h1 class="app-page-title">Settlement Preview</h1>
            <span>{{ $user->name }}</span>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    @foreach($preview as $label => $value)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ ucfirst(str_replace('_', ' ', $label)) }}</div>
                                <div class="fw-semibold mt-1">{{ is_array($value) ? json_encode($value) : $value }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
