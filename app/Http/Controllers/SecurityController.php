<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;

/**
 * Account security page: two-factor sign-in and recent sign-in activity.
 */
class SecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AuditLogger $audit,
    ) {}

    public function show(Request $request)
    {
        $user = $request->user();

        $settingUp = $user->two_factor_secret && !$user->two_factor_confirmed_at;

        $recentSignIns = AuditLog::where('auditable_type', $user->getMorphClass())
            ->where('auditable_id', $user->id)
            ->whereIn('event', ['login', 'login_failed', 'two_factor_failed', 'recovery_code_used'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('profile.security', [
            'user'              => $user,
            'settingUp'         => $settingUp,
            'qrCode'            => $settingUp ? $this->twoFactor->qrCodeSvg($user) : null,
            'secret'            => $settingUp ? $this->twoFactor->formattedSecret($user) : null,
            'recoveryCodesLeft' => count($this->twoFactor->recoveryCodes($user)),
            // Shown once, straight after setup or regeneration.
            'newRecoveryCodes'  => session('recovery_codes'),
            'recentSignIns'     => $recentSignIns,
        ]);
    }

    public function enable(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two-factor sign-in is already on.');
        }

        $this->twoFactor->beginSetup($user);

        return redirect()->route('profile.security');
    }

    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:10']);
        $user = $request->user();

        $codes = $this->twoFactor->confirm($user, $data['code']);

        if ($codes === null) {
            return back()->withErrors([
                'code' => 'That code is not right. Make sure the time on your phone is set automatically, then try the newest code.',
            ]);
        }

        $this->audit->record('two_factor_enabled', $user, [], [], 'Turned on by the user');

        return redirect()->route('profile.security')
            ->with('success', 'Two-factor sign-in is on. Save your recovery codes now.')
            ->with('recovery_codes', $codes);
    }

    public function cancelSetup(Request $request)
    {
        $user = $request->user();

        if (!$user->hasTwoFactorEnabled()) {
            $this->twoFactor->disable($user);
        }

        return redirect()->route('profile.security');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $this->twoFactor->disable($request->user());
        $this->audit->record('two_factor_disabled', $request->user(), [], [], 'Turned off by the user');

        return redirect()->route('profile.security')->with('success', 'Two-factor sign-in is off.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $user = $request->user();

        if (!$user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Turn on two-factor sign-in first.');
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);
        $this->audit->record('recovery_codes_regenerated', $user, [], [], 'New recovery codes created; old ones no longer work');

        return redirect()->route('profile.security')
            ->with('success', 'New recovery codes created. The old ones no longer work.')
            ->with('recovery_codes', $codes);
    }

    /** Admin: switch off two-factor for someone who has lost their phone. */
    public function adminReset(Request $request, User $staff)
    {
        abort_unless($request->user()->isAdmin(), 403);

        if (!$staff->two_factor_secret) {
            return back()->with('error', "{$staff->name} does not have two-factor sign-in on.");
        }

        $this->twoFactor->disable($staff);
        $this->audit->record('two_factor_reset', $staff, [], [], "Reset by {$request->user()->name}");

        return back()->with('success', "Two-factor sign-in reset for {$staff->name}. They can sign in with their password and set it up again.");
    }
}
