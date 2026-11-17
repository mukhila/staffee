<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Second step of sign-in for users with two-factor turned on.
 */
class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'two_factor_login';

    /** How long after the password step the code must be entered. */
    public const CHALLENGE_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AuditLogger $audit,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (!$this->pendingUser($request)) {
            return redirect()->route('login')->with('status', 'Please sign in again.');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (!$user) {
            return redirect()->route('login')->with('status', 'That took too long. Please sign in again.');
        }

        $data = $request->validate([
            'code'          => 'nullable|string|max:20',
            'recovery_code' => 'nullable|string|max:20',
        ]);

        $throttleKey = 'two-factor:' . $user->id . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'code' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $usingRecovery = filled($data['recovery_code'] ?? null);

        $valid = $usingRecovery
            ? $this->twoFactor->useRecoveryCode($user, $data['recovery_code'])
            : filled($data['code'] ?? null) && $this->twoFactor->verifyCode($user, $data['code']);

        if (!$valid) {
            RateLimiter::hit($throttleKey);
            $this->audit->record('two_factor_failed', $user, [], [], $usingRecovery ? 'Wrong recovery code' : 'Wrong authenticator code', $user->id);

            throw ValidationException::withMessages([
                $usingRecovery ? 'recovery_code' : 'code' => $usingRecovery
                    ? 'That recovery code is not valid or has already been used.'
                    : 'That code is not right. Check your authenticator app and try the newest code.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $remember = (bool) $request->session()->get(self::SESSION_KEY . '.remember', false);
        $request->session()->forget(self::SESSION_KEY);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($usingRecovery) {
            $left = count($this->twoFactor->recoveryCodes($user));
            $this->audit->record('recovery_code_used', $user, [], [], "Recovery code used, {$left} left", $user->id);

            return redirect()->route('profile.security')->with(
                'warning',
                "You signed in with a recovery code. You have {$left} left. If you have lost your phone, set up two-factor again."
            );
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (!is_array($pending) || ($pending['expires'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        $user = User::find($pending['id'] ?? null);

        return $user && $user->hasTwoFactorEnabled() ? $user : null;
    }
}
