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
  .auth-input-wrap .toggle-pwd        { border-left: 0; border-right: 1.5px solid #e2e8f8; border-radius: 0 10px 10px 0 !important; background: #f5f7ff; color: #94a3b8; cursor: pointer; }
  .auth-input-wrap .form-control.has-toggle { border-right: 0; border-radius: 0 !important; }
  .auth-btn    { background: linear-gradient(135deg, #316AFF 0%, #5b8def 100%); color: #fff; border: 0; border-radius: 12px; padding: .78rem 1rem; font-weight: 600; font-size: .95rem; width: 100%; transition: opacity .2s; }
  .auth-btn:hover { opacity: .9; color: #fff; }
  .auth-link   { color: #316AFF; font-weight: 600; text-decoration: none; }
  .auth-link:hover { text-decoration: underline; }
  .pw-req      { font-size: .75rem; display: flex; align-items: center; gap: .4rem; color: #9ca3af; transition: color .2s; }
  .pw-req.met  { color: #22c55e; }
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
        Set Your<br>New Password.<br>
        <span style="color:rgba(255,255,255,.55);">Stay Secure.</span>
      </h2>
      <p style="color:rgba(255,255,255,.58);margin-top:1rem;max-width:290px;line-height:1.75;font-size:.92rem;">
        Choose a strong password to protect your Staffee account.
      </p>

      <div class="mt-5" style="max-width:300px;">
        <p style="color:rgba(255,255,255,.5);font-size:.78rem;margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600;">Password tips</p>
        @foreach(['Use at least 8 characters','Mix uppercase & lowercase letters','Include numbers and symbols','Avoid personal information'] as $tip)
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="fi fi-rr-info" style="color:rgba(255,255,255,.4);font-size:.75rem;flex-shrink:0;"></i>
          <span style="color:rgba(255,255,255,.6);font-size:.82rem;">{{ $tip }}</span>
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
          <i class="fi fi-rr-lock" style="color:#fff;font-size:1.3rem;line-height:1;"></i>
        </div>
        <h4 style="font-weight:800;color:#0f172a;margin-bottom:.25rem;">Reset password</h4>
        <p class="text-muted" style="font-size:.875rem;">Enter and confirm your new password below</p>
      </div>

      <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email (readonly) --}}
        <div class="mb-3 auth-input-wrap">
          <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="email">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-envelope"></i></span>
            <input type="email" id="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="username"
                   style="border-left:0;border-radius:0 10px 10px 0 !important;">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- New Password --}}
        <div class="mb-2 auth-input-wrap">
          <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="password">New Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-lock"></i></span>
            <input type="password" id="password" name="password"
                   class="form-control has-toggle @error('password') is-invalid @enderror"
                   placeholder="Min 8 characters" required autocomplete="new-password"
                   oninput="checkReqs(this.value)">
            <button type="button" class="input-group-text toggle-pwd" onclick="togglePwd('password',this)" tabindex="-1">
              <i class="fi fi-rr-eye"></i>
            </button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        {{-- Requirements --}}
        <div class="mb-3 ps-1 d-flex flex-wrap gap-2">
          <span class="pw-req" id="req-len"><i class="fi fi-rr-circle"></i> 8+ chars</span>
          <span class="pw-req" id="req-up"><i class="fi fi-rr-circle"></i> Uppercase</span>
          <span class="pw-req" id="req-num"><i class="fi fi-rr-circle"></i> Number</span>
          <span class="pw-req" id="req-sym"><i class="fi fi-rr-circle"></i> Symbol</span>
        </div>

        {{-- Confirm Password --}}
        <div class="mb-4 auth-input-wrap">
          <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="password_confirmation">Confirm Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-shield-check"></i></span>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control has-toggle @error('password_confirmation') is-invalid @enderror"
                   placeholder="Repeat your new password" required autocomplete="new-password">
            <button type="button" class="input-group-text toggle-pwd" onclick="togglePwd('password_confirmation',this)" tabindex="-1">
              <i class="fi fi-rr-eye"></i>
            </button>
            @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <button type="submit" class="auth-btn waves-effect waves-light">
          Reset Password &nbsp;<i class="fi fi-rr-check"></i>
        </button>

        <div class="text-center mt-3">
          <a href="{{ route('login') }}" class="auth-link d-inline-flex align-items-center gap-1" style="font-size:.83rem;">
            <i class="fi fi-rr-arrow-left" style="font-size:.75rem;"></i> Back to sign in
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  const icon = btn.querySelector('i');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  icon.className = inp.type === 'text' ? 'fi fi-rr-eye-crossed' : 'fi fi-rr-eye';
}
function checkReqs(val) {
  const rules = {
    'req-len': val.length >= 8,
    'req-up':  /[A-Z]/.test(val),
    'req-num': /[0-9]/.test(val),
    'req-sym': /[^A-Za-z0-9]/.test(val),
  };
  for (const [id, met] of Object.entries(rules)) {
    const el = document.getElementById(id);
    el.classList.toggle('met', met);
    el.querySelector('i').className = met ? 'fi fi-rr-check-circle' : 'fi fi-rr-circle';
  }
}
</script>
</x-guest-layout>
