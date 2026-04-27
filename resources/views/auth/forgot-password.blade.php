<x-guest-layout>
<style>
  .auth-split { min-height: 100vh; display: flex; font-family: 'Plus Jakarta Sans', sans-serif; }
  .auth-brand  { width: 46%; background: linear-gradient(150deg, #060f2e 0%, #0d2369 35%, #316AFF 75%, #5b8def 100%); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; padding: 2.5rem 2.75rem; }
  .auth-form   { flex: 1; background: #f0f4ff; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1.5rem; }
  .auth-card   { width: 100%; max-width: 430px; background: #fff; border-radius: 24px; box-shadow: 0 8px 40px rgba(49,106,255,.10); padding: 2.5rem 2.25rem; }
  .auth-deco   { position: absolute; border-radius: 50%; }
  .auth-input-wrap .form-control, .auth-input-wrap .input-group-text { background: #f5f7ff; border: 1.5px solid #e2e8f8; }
  .auth-input-wrap .form-control:focus { background: #fff; border-color: #316AFF; box-shadow: 0 0 0 3px rgba(49,106,255,.12); }
  .auth-input-wrap .input-group-text  { border-right: 0; color: #94a3b8; border-radius: 10px 0 0 10px !important; }
  .auth-input-wrap .form-control      { border-left: 0; border-radius: 0 10px 10px 0 !important; }
  .auth-btn    { background: linear-gradient(135deg, #316AFF 0%, #5b8def 100%); color: #fff; border: 0; border-radius: 12px; padding: .78rem 1rem; font-weight: 600; font-size: .95rem; width: 100%; transition: opacity .2s; }
  .auth-btn:hover { opacity: .9; color: #fff; }
  .auth-link   { color: #316AFF; font-weight: 600; text-decoration: none; }
  .auth-link:hover { text-decoration: underline; }
  .auth-step   { display: flex; align-items: flex-start; gap: .75rem; padding: .9rem 1rem; border-radius: 12px; background: #f8faff; border: 1px solid #e8eeff; margin-bottom: .6rem; }
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
        Password<br>Recovery.<br>
        <span style="color:rgba(255,255,255,.55);">Quick & Secure.</span>
      </h2>
      <p style="color:rgba(255,255,255,.58);margin-top:1rem;max-width:290px;line-height:1.75;font-size:.92rem;">
        We'll send a secure link to your email so you can set a new password.
      </p>

      <div class="mt-4" style="max-width:300px;">
        @php $steps = [['fi fi-rr-envelope','Enter your registered email address'],['fi fi-rr-inbox','Check your inbox for the reset link'],['fi fi-rr-lock','Set a strong new password']]; @endphp
        @foreach($steps as $i => $step)
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:32px;height:32px;min-width:32px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <span style="color:#fff;font-size:.72rem;font-weight:800;">{{ $i+1 }}</span>
          </div>
          <span style="color:rgba(255,255,255,.7);font-size:.85rem;">{{ $step[1] }}</span>
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
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center mb-3"
             style="width:56px;height:56px;background:linear-gradient(135deg,#316AFF,#5b8def);border-radius:16px;box-shadow:0 4px 16px rgba(49,106,255,.3);">
          <i class="fi fi-rr-key" style="color:#fff;font-size:1.3rem;line-height:1;"></i>
        </div>
        <h4 style="font-weight:800;color:#0f172a;margin-bottom:.25rem;">Forgot password?</h4>
        <p class="text-muted" style="font-size:.875rem;">No worries — we'll send you a reset link</p>
      </div>

      <x-auth-session-status class="mb-3" :status="session('status')" />

      @if(session('status'))
      <div class="alert border-0 mb-4 p-3" style="background:#f0fdf4;border-radius:12px;">
        <div class="d-flex align-items-center gap-2">
          <i class="fi fi-rr-check-circle" style="color:#22c55e;font-size:1.1rem;"></i>
          <span style="font-size:.85rem;color:#166534;">{{ session('status') }}</span>
        </div>
      </div>
      @else
      <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4 auth-input-wrap">
          <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="email">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-envelope"></i></span>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="you@company.com"
                   required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <button type="submit" class="auth-btn waves-effect waves-light">
          Send Reset Link &nbsp;<i class="fi fi-rr-paper-plane"></i>
        </button>
      </form>
      @endif

      <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="auth-link d-inline-flex align-items-center gap-1" style="font-size:.83rem;">
          <i class="fi fi-rr-arrow-left" style="font-size:.75rem;"></i> Back to sign in
        </a>
      </div>
    </div>
  </div>
</div>
</x-guest-layout>
