<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">View Email</h1>
          </div>
          <a href="{{ route('mail.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-1">{{ $email->subject }}</h5>
                <div class="text-muted small">
                    From: {{ $email->from->name }} | To: {{ $email->to->name }} | {{ $email->created_at->format('M d, Y H:i') }}
                </div>
            </div>
            <div class="card-body">
                <p class="card-text" style="white-space: pre-wrap;">{{ $email->body }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
