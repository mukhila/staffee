<x-guest-layout>
<style>
  .auth-split { min-height: 100vh; display: flex; font-family: 'Plus Jakarta Sans', sans-serif; }
  .auth-brand  { width: 46%; background: linear-gradient(150deg, #060f2e 0%, #0d2369 35%, #316AFF 75%, #5b8def 100%); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; padding: 2.5rem 2.75rem; }
  .auth-form   { flex: 1; background: #f0f4ff; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1.5rem; }
  .auth-card   { width: 100%; max-width: 430px; background: #fff; border-radius: 24px; box-shadow: 0 8px 40px rgba(49,106,255,.10); padding: 2.5rem 2.25rem; }
  .auth-deco   { position: absolute; border-radius: 50%; }
  .auth-input-wrap .form-control, .auth-input-wrap .input-group-text { background: #f5f7ff; border: 1.5px solid #e2e8f8; }
  .auth-input-wrap .form-control:focus { background: #fff; border-color: #316AFF; box-shadow: 0 0 0 3px rgba(49,106,255,.12); }
  .auth-input-wrap .input-group-text  { border-right: 0; color: #94a3b8; }
  .auth-input-wrap .form-control      { border-left: 0; border-radius: 0 10px 10px 0 !important; }
  .auth-input-wrap .input-group-text:first-child { border-radius: 10px 0 0 10px !important; }
  .auth-input-wrap .toggle-pwd        { border-left: 0; border-right: 1.5px solid #e2e8f8; border-radius: 0 10px 10px 0 !important; background: #f5f7ff; color: #94a3b8; cursor: pointer; }
  .auth-input-wrap .form-control.has-toggle { border-right: 0; border-radius: 0 !important; }
  .auth-btn    { background: linear-gradient(135deg, #316AFF 0%, #5b8def 100%); color: #fff; border: 0; border-radius: 12px; padding: .78rem 1rem; font-weight: 600; font-size: .95rem; width: 100%; transition: opacity .2s; }
  .auth-btn:hover { opacity: .9; color: #fff; }
  .auth-stat   { background: rgba(255,255,255,.10); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: .9rem 1.1rem; flex: 1; min-width: 100px; }
  .auth-link   { color: #316AFF; font-weight: 600; text-decoration: none; }
  .auth-link:hover { text-decoration: underline; }
  @media (max-width: 991.98px) { .auth-brand { display: none !important; } }
</style>

<div class="auth-split">

  {{-- ── Left brand panel ──────────────────────────────── --}}
  <div class="auth-brand d-none d-lg-flex">
    <div class="auth-deco" style="width:340px;height:340px;background:rgba(255,255,255,.05);top:-100px;right:-100px;"></div>
    <div class="auth-deco" style="width:180px;height:180px;background:rgba(255,255,255,.06);bottom:120px;left:-60px;"></div>
    <div class="auth-deco" style="width:90px;height:90px;background:rgba(255,255,255,.07);bottom:260px;right:80px;"></div>

    {{-- Brand --}}
    <div style="position:relative;z-index:1;">
      <div class="d-flex align-items-center gap-3 mb-5">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee"
             style="width:52px;height:52px;border-radius:14px;border:2px solid rgba(255,255,255,.2);object-fit:cover;box-shadow:0 4px 16px rgba(0,0,0,.25);">
        <span style="color:#fff;font-size:1.35rem;font-weight:800;letter-spacing:-.4px;">Staffee</span>
      </div>
      <h2 style="color:#fff;font-size:2.1rem;font-weight:800;line-height:1.25;max-width:300px;">
        Smart Staff<br>Management.<br>
        <span style="color:rgba(255,255,255,.55);">Built for Teams.</span>
      </h2>
      <p style="color:rgba(255,255,255,.58);margin-top:1rem;max-width:290px;line-height:1.75;font-size:.92rem;">
        HR, attendance, payroll, leaves, monitoring — everything your organization needs in one place.
      </p>
    </div>

    {{-- Stats --}}
    <div style="position:relative;z-index:1;">
      <div class="d-flex gap-2 flex-wrap">
        <div class="auth-stat">
          <div style="color:#fff;font-size:1.35rem;font-weight:800;">Auto</div>
          <div style="color:rgba(255,255,255,.6);font-size:.75rem;margin-top:.1rem;">Payroll</div>
        </div>
        <div class="auth-stat">
          <div style="color:#fff;font-size:1.35rem;font-weight:800;">Live</div>
          <div style="color:rgba(255,255,255,.6);font-size:.75rem;margin-top:.1rem;">Monitoring</div>
        </div>
        <div class="auth-stat">
          <div style="color:#fff;font-size:1.35rem;font-weight:800;">100+</div>
          <div style="color:rgba(255,255,255,.6);font-size:.75rem;margin-top:.1rem;">Features</div>
        </div>
      </div>
      <p style="color:rgba(255,255,255,.35);font-size:.72rem;margin-top:1.5rem;">
        © {{ date('Y') }} Staffee. All rights reserved.
      </p>
    </div>
  </div>

  {{-- ── Right form panel ──────────────────────────────── --}}
  <div class="auth-form">

    {{-- Mobile logo --}}
    <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
      <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee"
           style="width:36px;height:36px;border-radius:10px;object-fit:cover;">
      <span style="font-weight:800;font-size:1.05rem;color:#0d2369;">Staffee</span>
    </div>

    <div class="auth-card">
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center mb-3"
             style="width:56px;height:56px;background:linear-gradient(135deg,#316AFF,#5b8def);border-radius:16px;box-shadow:0 4px 16px rgba(49,106,255,.3);">
          <i class="fi fi-rr-sign-in-alt" style="color:#fff;font-size:1.3rem;line-height:1;"></i>
        </div>
        <h4 style="font-weight:800;color:#0f172a;margin-bottom:.25rem;">Welcome back</h4>
        <p class="text-muted" style="font-size:.875rem;">Sign in to access your workspace</p>
      </div>

      <x-auth-session-status class="mb-3" :status="session('status')" />

      <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-3 auth-input-wrap">
          <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="email">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-envelope"></i></span>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="you@company.com"
                   required autofocus autocomplete="username">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Password --}}
        <div class="mb-3 auth-input-wrap">
          <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="password">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-lock"></i></span>
            <input type="password" id="password" name="password"
                   class="form-control has-toggle @error('password') is-invalid @enderror"
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="input-group-text toggle-pwd" onclick="togglePwd('password',this)" tabindex="-1">
              <i class="fi fi-rr-eye"></i>
            </button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Remember + Forgot --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
            <label class="form-check-label" style="font-size:.83rem;color:#6b7280;" for="remember_me">Remember me</label>
          </div>
          @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="auth-link" style="font-size:.83rem;">Forgot password?</a>
          @endif
        </div>

        <button type="submit" class="auth-btn waves-effect waves-light">
          Sign In &nbsp;<i class="fi fi-rr-arrow-right"></i>
        </button>
      </form>
    </div>

    <p class="text-center mt-4" style="font-size:.82rem;color:#6b7280;">
      Need help? Contact your system administrator.
    </p>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'fi fi-rr-eye-crossed';
  } else {
    inp.type = 'password';
    icon.className = 'fi fi-rr-eye';
  }
}
</script>
</x-guest-layout>
