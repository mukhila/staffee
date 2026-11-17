<x-app-layout>
@php
    $endpoints = [
        ['GET',    '/me',                     'read',  'Your profile'],
        ['GET',    '/announcements',          'read',  'Announcements for you'],
        ['GET',    '/attendance/today',       'read',  "Today's attendance, and whether a location is required"],
        ['GET',    '/attendance?month=YYYY-MM','read', 'Attendance for a month'],
        ['POST',   '/attendance/check-in',    'write', 'Check in. Send latitude and longitude if the office geofence is on'],
        ['POST',   '/attendance/check-out',   'write', 'Check out'],
        ['GET',    '/leave/types',            'read',  'Leave types with your available days'],
        ['GET',    '/leave',                  'read',  'Your leave requests'],
        ['POST',   '/leave',                  'write', 'Request leave: leave_type_id, from_date, to_date, reason'],
        ['DELETE', '/leave/{id}',             'write', 'Cancel a leave request'],
        ['GET',    '/payslips',               'read',  'Your published payslips'],
        ['GET',    '/payslips/{id}/pdf',      'read',  'Download a payslip as PDF'],
    ];
    $methodColors = ['GET' => 'info', 'POST' => 'success', 'DELETE' => 'danger'];
@endphp
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">API Tokens</h1>
            <span>Let a mobile app or script use Staffee as you</span>
        </div>
        <a href="{{ route('profile.security') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-shield-check me-1"></i> Security
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- New token, shown once --}}
    @if($newToken)
    <div class="card border-warning mb-3">
        <div class="card-header bg-warning-subtle"><h6 class="mb-0"><i class="fi fi-rr-key me-1"></i> Your new token</h6></div>
        <div class="card-body">
            <p class="small mb-2">Copy it now and keep it secret, like a password. <strong>It will not be shown again.</strong></p>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" id="new-token" value="{{ $newToken }}" readonly aria-label="New API token">
                <button class="btn btn-outline-primary" type="button" id="copy-token"><i class="fi fi-rr-copy me-1"></i> Copy</button>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Create a token</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.api-tokens.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" id="name" name="name" maxlength="60" value="{{ old('name') }}" placeholder="e.g. My phone"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">So you can tell your tokens apart later.</div>
                        </div>
                        <fieldset class="mb-3">
                            <legend class="form-label fs-6">What it can do</legend>
                            @foreach($abilities as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="{{ $value }}" id="ability-{{ $value }}"
                                       {{ in_array($value, old('abilities', ['read'])) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="ability-{{ $value }}">{{ $label }}</label>
                            </div>
                            @endforeach
                            @error('abilities')<div class="text-danger small">{{ $message }}</div>@enderror
                        </fieldset>
                        <div class="mb-3">
                            <label class="form-label" for="lifetime">Expires after</label>
                            <select id="lifetime" name="lifetime" class="form-select">
                                @foreach($lifetimes as $days => $label)
                                <option value="{{ $days }}" {{ (string) old('lifetime', 90) === (string) $days ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create token</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Your tokens</h6>
                    <span class="small text-muted">{{ $tokens->count() }} of {{ $maxTokens }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Can</th><th>Last used</th><th>Expires</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($tokens as $token)
                            @php $expired = $token->expires_at && $token->expires_at->isPast(); @endphp
                            <tr class="{{ $expired ? 'text-muted' : '' }}">
                                <td class="fw-medium">{{ $token->name }}</td>
                                <td>
                                    @foreach($token->abilities as $ability)
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $ability }}</span>
                                    @endforeach
                                </td>
                                <td class="small">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="small">
                                    @if($expired)
                                    <span class="badge bg-danger-subtle text-danger">Expired</span>
                                    @else
                                    {{ $token->expires_at?->format('d M Y') ?? 'Never' }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('profile.api-tokens.destroy', $token->id) }}"
                                          onsubmit="return confirm('Revoke {{ addslashes($token->name) }}? Anything using it will stop working.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">You have no API tokens.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Using the API</h6>
                    <small class="text-muted">Send the token in the Authorization header. Each call only sees and changes your own records. Limit: 60 calls a minute.</small>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-2 rounded small mb-3" style="white-space: pre-wrap">curl {{ $apiBase }}/me \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"</pre>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 small">
                            <thead><tr><th>Method</th><th>Path</th><th>Needs</th><th>What it does</th></tr></thead>
                            <tbody>
                                @foreach($endpoints as [$method, $path, $ability, $description])
                                <tr>
                                    <td><span class="badge bg-{{ $methodColors[$method] }}">{{ $method }}</span></td>
                                    <td class="font-monospace text-nowrap">{{ $path }}</td>
                                    <td>{{ $ability }}</td>
                                    <td>{{ $description }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">All paths start with <code>{{ $apiBase }}</code>. Dates are YYYY-MM-DD. Errors return JSON with a <code>message</code>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@if($newToken)
@push('scripts')
<script>
document.getElementById('copy-token')?.addEventListener('click', function () {
    const field = document.getElementById('new-token');
    navigator.clipboard?.writeText(field.value).then(() => { this.innerHTML = '<i class="fi fi-rr-check me-1"></i> Copied'; });
});
</script>
@endpush
@endif
</x-app-layout>
