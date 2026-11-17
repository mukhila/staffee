<x-guest-layout>
<style>
  .tf-wrap  { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f0f4ff; padding: 2rem 1rem; font-family: 'Plus Jakarta Sans', sans-serif; }
  .tf-card  { width: 100%; max-width: 420px; background: #fff; border-radius: 24px; box-shadow: 0 8px 40px rgba(49,106,255,.10); padding: 2.5rem 2.25rem; }
  .tf-code  { font-size: 1.6rem; letter-spacing: .5rem; text-align: center; font-weight: 700; background: #f5f7ff; border: 1.5px solid #e2e8f8; border-radius: 12px; }
  .tf-code:focus { background: #fff; border-color: #316AFF; box-shadow: 0 0 0 3px rgba(49,106,255,.12); }
  .tf-btn   { background: linear-gradient(135deg, #316AFF 0%, #5b8def 100%); color: #fff; border: 0; border-radius: 12px; padding: .78rem 1rem; font-weight: 600; width: 100%; }
  .tf-btn:hover { opacity: .9; color: #fff; }
  .tf-link  { color: #316AFF; font-weight: 600; text-decoration: none; background: none; border: 0; padding: 0; font-size: .85rem; }
  .tf-link:hover { text-decoration: underline; }
</style>

<div class="tf-wrap">
  <div class="tf-card">
    <div class="text-center mb-4">
      <div class="d-inline-flex align-items-center justify-content-center mb-3"
           style="width:56px;height:56px;background:linear-gradient(135deg,#316AFF,#5b8def);border-radius:16px;box-shadow:0 4px 16px rgba(49,106,255,.3);">
        <i class="fi fi-rr-shield-check" style="color:#fff;font-size:1.3rem;line-height:1;"></i>
      </div>
      <h4 style="font-weight:800;color:#0f172a;margin-bottom:.25rem;">Two-step verification</h4>
      <p class="text-muted mb-0" id="tf-help-code" style="font-size:.875rem;">Open your authenticator app and enter the 6-digit code for Staffee.</p>
      <p class="text-muted mb-0 d-none" id="tf-help-recovery" style="font-size:.875rem;">Enter one of the recovery codes you saved when you set up two-step verification.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge') }}" id="tf-form">
      @csrf

      {{-- Authenticator code --}}
      <div class="mb-3" id="tf-code-group">
        <label class="visually-hidden" for="code">Authentication code</label>
        <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
               autocomplete="one-time-code" autofocus placeholder="000000"
               class="form-control tf-code @error('code') is-invalid @enderror">
        @error('code')<div class="invalid-feedback text-center">{{ $message }}</div>@enderror
      </div>

      {{-- Recovery code --}}
      <div class="mb-3 d-none" id="tf-recovery-group">
        <label class="form-label fw-semibold" style="font-size:.82rem;color:#374151;" for="recovery_code">Recovery code</label>
        <input type="text" id="recovery_code" name="recovery_code" maxlength="20" autocomplete="off" placeholder="XXXX-XXXX"
               class="form-control text-center text-uppercase @error('recovery_code') is-invalid @enderror" style="letter-spacing:.15rem;">
        @error('recovery_code')<div class="invalid-feedback text-center">{{ $message }}</div>@enderror
      </div>

      <button type="submit" class="tf-btn">Verify &nbsp;<i class="fi fi-rr-arrow-right"></i></button>
    </form>

    <div class="text-center mt-3">
      <button type="button" class="tf-link" id="tf-toggle">Lost your phone? Use a recovery code</button>
    </div>

    <hr class="my-4">

    <form method="POST" action="{{ route('logout') }}" class="text-center">
      @csrf
      <button type="submit" class="tf-link text-muted">Cancel and go back to sign in</button>
    </form>
    <p class="text-center text-muted mt-3 mb-0" style="font-size:.78rem;">
      No phone and no recovery codes? Ask your administrator to reset two-step verification for your account.
    </p>
  </div>
</div>

<script>
(function () {
  const toggle   = document.getElementById('tf-toggle');
  const codeGrp  = document.getElementById('tf-code-group');
  const recGrp   = document.getElementById('tf-recovery-group');
  const helpCode = document.getElementById('tf-help-code');
  const helpRec  = document.getElementById('tf-help-recovery');
  const code     = document.getElementById('code');
  const recovery = document.getElementById('recovery_code');

  function show(useRecovery) {
    codeGrp.classList.toggle('d-none', useRecovery);
    helpCode.classList.toggle('d-none', useRecovery);
    recGrp.classList.toggle('d-none', !useRecovery);
    helpRec.classList.toggle('d-none', !useRecovery);
    toggle.textContent = useRecovery ? 'Use the code from my authenticator app instead' : 'Lost your phone? Use a recovery code';
    // Only send the field that is showing.
    (useRecovery ? code : recovery).value = '';
    (useRecovery ? recovery : code).focus();
  }

  let usingRecovery = {{ $errors->has('recovery_code') ? 'true' : 'false' }};
  if (usingRecovery) show(true);

  toggle.addEventListener('click', function () { usingRecovery = !usingRecovery; show(usingRecovery); });

  // Submit as soon as six digits are typed or pasted.
  code.addEventListener('input', function () {
    if (code.value.replace(/\D/g, '').length === 6) {
      document.getElementById('tf-form').submit();
    }
  });
})();
</script>
</x-guest-layout>
