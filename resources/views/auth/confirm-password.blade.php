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
        Secure Area.<br>Identity<br>
        <span style="color:rgba(255,255,255,.55);">Verification.</span>
      </h2>
      <p style="color:rgba(255,255,255,.58);margin-top:1rem;max-width:290px;line-height:1.75;font-size:.92rem;">
        You're about to access a sensitive section of Staffee. Please confirm it's really you.
      </p>

      <div class="mt-5 p-4" style="background:rgba(255,255,255,.08);border-radius:16px;border:1px solid rgba(255,255,255,.12);">
        <div class="d-flex align-items-center gap-3 mb-2">
          <i class="fi fi-rr-shield-check" style="color:#fff;font-size:1.5rem;"></i>
          <span style="color:#fff;font-weight:700;">Security First</span>
        </div>
        <p style="color:rgba(255,255,255,.6);font-size:.82rem;margin:0;line-height:1.65;">
          This extra step ensures that sensitive operations are authorized. Your password is never stored in plain text.
        </p>
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
             style="width:56px;height:56px;background:linear-gradient(135deg,#f97316,#fb923c);border-radius:16px;box-shadow:0 4px 16px rgba(249,115,22,.3);">
          <i class="fi fi-rr-shield-check" style="color:#fff;font-size:1.3rem;line-height:1;"></i>
        </div>
        <h4 style="font-weight:800;color:#0f172a;margin-bottom:.25rem;">Confirm your identity</h4>
        <p class="text-muted" style="font-size:.875rem;">Enter your password to access this secure area</p>
      </div>

      <div class="mb-4 p-3" style="background:#fff8f0;border-radius:12px;border:1px solid #fed7aa;">
        <div class="d-flex align-items-start gap-2">
          <i class="fi fi-rr-info" style="color:#f97316;margin-top:1px;font-size:.9rem;flex-shrink:0;"></i>
          <p style="font-size:.82rem;color:#9a3412;margin:0;line-height:1.6;">
            This is a secure area. Please re-enter your password to continue.
          </p>
        </div>
      </div>

      <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-4 auth-input-wrap">
          <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="password">Current Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-lock"></i></span>
            <input type="password" id="password" name="password"
                   class="form-control has-toggle @error('password') is-invalid @enderror"
                   placeholder="Your account password" required autocomplete="current-password">
            <button type="button" class="input-group-text toggle-pwd" onclick="togglePwd('password',this)" tabindex="-1">
              <i class="fi fi-rr-eye"></i>
            </button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <button type="submit" class="auth-btn waves-effect waves-light" style="background:linear-gradient(135deg,#f97316,#fb923c);">
          Confirm &amp; Continue &nbsp;<i class="fi fi-rr-arrow-right"></i>
        </button>
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
</script>
</x-guest-layout>
