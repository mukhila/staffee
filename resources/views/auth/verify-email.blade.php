<x-guest-layout>
<style>
  .auth-split { min-height: 100vh; display: flex; font-family: 'Plus Jakarta Sans', sans-serif; }
  .auth-brand  { width: 46%; background: linear-gradient(150deg, #060f2e 0%, #0d2369 35%, #316AFF 75%, #5b8def 100%); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; padding: 2.5rem 2.75rem; }
  .auth-form   { flex: 1; background: #f0f4ff; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1.5rem; }
  .auth-card   { width: 100%; max-width: 440px; background: #fff; border-radius: 24px; box-shadow: 0 8px 40px rgba(49,106,255,.10); padding: 2.5rem 2.25rem; }
  .auth-deco   { position: absolute; border-radius: 50%; }
  .auth-btn    { background: linear-gradient(135deg, #316AFF 0%, #5b8def 100%); color: #fff; border: 0; border-radius: 12px; padding: .78rem 1rem; font-weight: 600; font-size: .95rem; width: 100%; transition: opacity .2s; }
  .auth-btn:hover { opacity: .9; color: #fff; }
  .auth-link   { color: #316AFF; font-weight: 600; text-decoration: none; }
  .auth-link:hover { text-decoration: underline; }
  .mail-anim   { animation: float 3s ease-in-out infinite; }
  @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
  @media (max-width: 991.98px) { .auth-brand { display: none !important; } }
</style>

<div class="auth-split">

  {{-- ── Left brand panel ──────────────────────────────── --}}
  <div class="auth-brand d-none d-lg-flex">
    <div class="auth-deco" style="width:340px;height:340px;background:rgba(255,255,255,.05);top:-100px;right:-100px;"></div>
    <div class="auth-deco" style="width:180px;height:180px;background:rgba(255,255,255,.06);bottom:120px;left:-60px;"></div>

    <div style="position:relative;z-index:1;">
      <div class="d-flex align-items-center gap-3 mb-5">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee"
             style="width:52px;height:52px;border-radius:14px;border:2px solid rgba(255,255,255,.2);object-fit:cover;box-shadow:0 4px 16px rgba(0,0,0,.25);">
        <span style="color:#fff;font-size:1.35rem;font-weight:800;letter-spacing:-.4px;">Staffee</span>
      </div>
      <h2 style="color:#fff;font-size:2.1rem;font-weight:800;line-height:1.25;max-width:300px;">
        Almost There.<br>Verify Your<br>
        <span style="color:rgba(255,255,255,.55);">Email Address.</span>
      </h2>
      <p style="color:rgba(255,255,255,.58);margin-top:1rem;max-width:290px;line-height:1.75;font-size:.92rem;">
        Email verification keeps your account safe and ensures you receive important notifications.
      </p>

      <div class="mt-5 d-flex flex-column gap-3" style="max-width:300px;">
        @foreach(['We sent a verification link to your inbox','Click the link in the email to verify','Return here once verified to get started'] as $step)
        <div class="d-flex align-items-center gap-3">
          <div style="width:8px;height:8px;min-width:8px;background:rgba(255,255,255,.5);border-radius:50%;"></div>
          <span style="color:rgba(255,255,255,.65);font-size:.85rem;">{{ $step }}</span>
        </div>
        @endforeach
      </div>
    </div>

    <div style="position:relative;z-index:1;">
      <p style="color:rgba(255,255,255,.35);font-size:.72rem;margin:0;">
        © {{ date('Y') }} Staffee. All rights reserved.
      </p>
    </div>
  </div>

  {{-- ── Right form panel ──────────────────────────────── --}}
  <div class="auth-form">

    <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
      <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee"
           style="width:36px;height:36px;border-radius:10px;object-fit:cover;">
      <span style="font-weight:800;font-size:1.05rem;color:#0d2369;">Staffee</span>
    </div>

    <div class="auth-card">

      {{-- Animated icon --}}
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center mb-3 mail-anim"
             style="width:72px;height:72px;background:linear-gradient(135deg,#316AFF,#5b8def);border-radius:20px;box-shadow:0 6px 24px rgba(49,106,255,.3);">
          <i class="fi fi-rr-envelope-open" style="color:#fff;font-size:1.7rem;line-height:1;"></i>
        </div>
        <h4 style="font-weight:800;color:#0f172a;margin-bottom:.25rem;">Check your email</h4>
        <p class="text-muted" style="font-size:.875rem;max-width:300px;margin:0 auto;">
          We sent a verification link to your email address. Click the link to activate your account.
        </p>
      </div>

      {{-- Success banner --}}
      @if (session('status') == 'verification-link-sent')
      <div class="mb-4 p-3" style="background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
        <div class="d-flex align-items-center gap-2">
          <i class="fi fi-rr-check-circle" style="color:#22c55e;font-size:1.1rem;flex-shrink:0;"></i>
          <span style="font-size:.85rem;color:#166534;line-height:1.5;">
            A new verification link has been sent to your registered email address.
          </span>
        </div>
      </div>
      @endif

      {{-- Info box --}}
      <div class="mb-4 p-3" style="background:#f0f4ff;border-radius:12px;border:1px solid #c7d7ff;">
        <div class="d-flex align-items-start gap-2">
          <i class="fi fi-rr-info" style="color:#316AFF;margin-top:1px;flex-shrink:0;"></i>
          <p style="font-size:.82rem;color:#374151;margin:0;line-height:1.65;">
            Didn't receive the email? Check your spam folder. If it's still not there, click <strong>Resend</strong> below.
          </p>
        </div>
      </div>

      {{-- Resend form --}}
      <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
        @csrf
        <button type="submit" class="auth-btn waves-effect waves-light">
          <i class="fi fi-rr-paper-plane me-2"></i>Resend Verification Email
        </button>
      </form>

      {{-- Divider --}}
      <div class="d-flex align-items-center gap-3 my-3">
        <div style="flex:1;height:1px;background:#e5e7eb;"></div>
        <span style="font-size:.75rem;color:#9ca3af;">or</span>
        <div style="flex:1;height:1px;background:#e5e7eb;"></div>
      </div>

      {{-- Logout --}}
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn w-100 fw-semibold"
                style="background:#f8faff;border:1.5px solid #e2e8f8;border-radius:12px;padding:.72rem;color:#6b7280;font-size:.88rem;transition:all .2s;">
          <i class="fi fi-rr-exit me-2" style="color:#94a3b8;"></i>Sign out of this account
        </button>
      </form>
    </div>

    <p class="text-center mt-4" style="font-size:.78rem;color:#9ca3af;">
      Having trouble? Contact <a href="mailto:support@staffee.com" class="auth-link" style="font-weight:500;">your admin</a>
    </p>
  </div>
</div>
</x-guest-layout>
