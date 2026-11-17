<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Security</h1>
            <span>Protect your account with two-step verification</span>
        </div>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-user me-1"></i> Profile
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">

            {{-- ── New recovery codes (shown once) ─────────────────────────── --}}
            @if($newRecoveryCodes)
            <div class="card border-warning mb-3">
                <div class="card-header bg-warning-subtle">
                    <h6 class="mb-0"><i class="fi fi-rr-key me-1"></i> Save your recovery codes</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-3">
                        If you lose your phone, each of these codes lets you sign in once. Keep them somewhere safe, such as a password manager.
                        <strong>They will not be shown again.</strong>
                    </p>
                    <div class="row g-2 mb-3" id="recovery-codes">
                        @foreach($newRecoveryCodes as $code)
                        <div class="col-6"><code class="d-block text-center p-2 bg-light rounded fs-6">{{ $code }}</code></div>
                        @endforeach
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="copy-codes">
                            <i class="fi fi-rr-copy me-1"></i> Copy
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="download-codes">
                            <i class="fi fi-rr-download me-1"></i> Download
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Two-factor status / setup ───────────────────────────────── --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Two-step verification</h6>
                    @if($user->hasTwoFactorEnabled())
                    <span class="badge bg-success">On</span>
                    @elseif($settingUp)
                    <span class="badge bg-warning">Setting up</span>
                    @else
                    <span class="badge bg-secondary">Off</span>
                    @endif
                </div>
                <div class="card-body">

                    @if($user->hasTwoFactorEnabled())
                    <p class="mb-2">When you sign in, you enter your password and then a code from your authenticator app.</p>
                    <p class="small text-muted mb-0">
                        Turned on {{ $user->two_factor_confirmed_at->format('d M Y') }} ·
                        {{ $recoveryCodesLeft }} of {{ \App\Services\TwoFactorService::RECOVERY_CODE_COUNT }} recovery codes left
                    </p>
                    @if($recoveryCodesLeft <= 2)
                    <div class="alert alert-warning small mt-3 mb-0">You are running low on recovery codes. Create new ones below.</div>
                    @endif

                    @elseif($settingUp)
                    <ol class="ps-3 mb-4">
                        <li class="mb-3">
                            Install an authenticator app on your phone, such as Google Authenticator, Microsoft Authenticator or Authy.
                        </li>
                        <li class="mb-3">
                            In the app, add an account and scan this QR code.
                            <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                                <div class="p-2 bg-white border rounded" style="width: 216px" role="img" aria-label="QR code for your authenticator app">{!! $qrCode !!}</div>
                                <div class="small">
                                    <div class="text-muted mb-1">Can't scan it? Enter this key instead:</div>
                                    <code class="d-inline-block p-2 bg-light rounded user-select-all" style="letter-spacing:.05rem">{{ $secret }}</code>
                                </div>
                            </div>
                        </li>
                        <li>
                            Enter the 6-digit code the app shows.
                            <form method="POST" action="{{ route('profile.security.two-factor.confirm') }}" class="d-flex gap-2 mt-2" style="max-width: 320px">
                                @csrf
                                <label class="visually-hidden" for="code">6-digit code</label>
                                <input type="text" id="code" name="code" inputmode="numeric" maxlength="7" autocomplete="one-time-code" placeholder="000000"
                                       class="form-control text-center fw-bold @error('code') is-invalid @enderror" style="letter-spacing:.3rem" required autofocus>
                                <button type="submit" class="btn btn-primary text-nowrap">Turn on</button>
                            </form>
                            @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </li>
                    </ol>
                    <form method="POST" action="{{ route('profile.security.two-factor.cancel') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-muted p-0">Cancel setup</button>
                    </form>

                    @else
                    <p class="mb-3">
                        Add a second step to signing in. Even if someone learns your password, they cannot get in without the code from your phone.
                    </p>
                    <form method="POST" action="{{ route('profile.security.two-factor.enable') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary"><i class="fi fi-rr-shield-check me-1"></i> Set up two-step verification</button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- ── Manage (only when on) ───────────────────────────────────── --}}
            @if($user->hasTwoFactorEnabled())
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Manage</h6></div>
                <div class="card-body">
                    <p class="small text-muted">Enter your password to make changes.</p>
                    @error('password')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror
                    <div class="row g-3">
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('profile.security.recovery-codes') }}">
                                @csrf
                                <label class="form-label small" for="password-regenerate">Password</label>
                                <input type="password" id="password-regenerate" name="password" class="form-control form-control-sm mb-2" autocomplete="current-password" required>
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Create new recovery codes</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('profile.security.two-factor.disable') }}"
                                  onsubmit="return confirm('Turn off two-step verification? Your account will be protected by your password only.');">
                                @csrf
                                @method('DELETE')
                                <label class="form-label small" for="password-disable">Password</label>
                                <input type="password" id="password-disable" name="password" class="form-control form-control-sm mb-2" autocomplete="current-password" required>
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">Turn off two-step verification</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- ── Recent sign-ins ──────────────────────────────────────────────── --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recent sign-in activity</h6>
                    <small class="text-muted">If you see something you don't recognise, change your password and tell your administrator.</small>
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse($recentSignIns as $entry)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-{{ $entry->event_color }}-subtle text-{{ $entry->event_color }}">{{ $entry->event_label }}</span>
                            <span class="text-muted" title="{{ $entry->created_at->format('d M Y H:i:s') }}">{{ $entry->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-muted mt-1">
                            {{ $entry->ip_address ?? 'Unknown IP' }}
                            @if($entry->user_agent) · {{ Str::limit($entry->user_agent, 60) }}@endif
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-muted">No sign-in activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@if($newRecoveryCodes)
@push('scripts')
<script>
(function () {
    const codes = @json($newRecoveryCodes).join('\n');
    document.getElementById('copy-codes')?.addEventListener('click', function () {
        navigator.clipboard?.writeText(codes).then(() => { this.innerHTML = '<i class="fi fi-rr-check me-1"></i> Copied'; });
    });
    document.getElementById('download-codes')?.addEventListener('click', function () {
        const blob = new Blob(['Staffee recovery codes\n\n' + codes + '\n\nEach code works once.\n'], { type: 'text/plain' });
        const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: 'staffee-recovery-codes.txt' });
        a.click();
        URL.revokeObjectURL(a.href);
    });
})();
</script>
@endpush
@endif
</x-app-layout>
