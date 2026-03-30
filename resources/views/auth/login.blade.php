<x-guest-layout>
    <div class="auth-wrapper min-vh-100 px-2" style="background-image: url({{ asset('assets/images/auth/bg_auth.png') }}); background-size: cover; background-position: center; background-repeat: no-repeat;">
      <div class="row g-0 min-vh-100">
        <div class="col-xl-5 col-lg-6 ms-auto px-sm-4 align-self-center py-4">
          <div class="card card-body p-4 p-sm-5 maxw-450px m-auto rounded-4 auth-card" data-simplebar>
            <div class="mb-4 text-center">
              <a href="{{ route('dashboard') }}" aria-label="GXON logo">
                <img class="visible-light" src="{{ asset('assets/images/logo-full.svg') }}" alt="GXON logo">
                <img class="visible-dark" src="{{ asset('assets/images/logo-full-white.svg') }}" alt="GXON logo">
              </a>
            </div>
            <div class="text-center mb-4">
              <h5 class="mb-1">Welcome to Staff Management</h5>
              <p>Sign in to access your dashboard.</p>
            </div>
            
            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

              <div class="mb-4">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="info@example.com">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password" placeholder="********">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="mb-4">
                <div class="d-flex justify-content-between">
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me"> Remember Me </label>
                  </div>
                  @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Forgot Password?</a>
                  @endif
                </div>
              </div>
              <div class="mb-3">
                <button type="submit" class="btn btn-primary waves-effect waves-light w-100">Login</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</x-guest-layout>
