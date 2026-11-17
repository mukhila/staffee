<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Time-based one-time codes (TOTP), as used by Google Authenticator,
 * Microsoft Authenticator, Authy and similar apps.
 */
class TwoFactorService
{
    public const RECOVERY_CODE_COUNT = 8;

    /** Accept the code from one 30-second step either side, for clock drift. */
    private const WINDOW = 1;

    public function __construct(private readonly Google2FA $google2fa) {}

    /** Start setup: store a new, not yet confirmed secret. */
    public function beginSetup(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'         => $this->google2fa->generateSecretKey(32),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();
    }

    /**
     * Finish setup once the user proves their app works.
     *
     * @return string[]|null the recovery codes to show once, or null if the code was wrong
     */
    public function confirm(User $user, string $code): ?array
    {
        if (!$user->two_factor_secret || !$this->verifyCode($user, $code)) {
            return null;
        }

        $codes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode($codes),
            'two_factor_confirmed_at'   => now(),
        ])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();
    }

    /** @return string[] */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => json_encode($codes)])->save();

        return $codes;
    }

    /**
     * Check a 6-digit code from the authenticator app. Each code can only be
     * used once, so a code seen over someone's shoulder cannot be replayed.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (!$user->two_factor_secret || !preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $cacheKey = "two-factor-last-step:{$user->id}";
        $lastStep = Cache::get($cacheKey);

        $step = $this->google2fa->verifyKeyNewer($user->two_factor_secret, $code, $lastStep, self::WINDOW);

        if ($step === false) {
            return false;
        }

        // verifyKeyNewer returns true (not a step) when there was no previous step.
        $step = $step === true ? $this->google2fa->getTimestamp() : $step;
        Cache::put($cacheKey, $step, now()->addMinutes(5));

        return true;
    }

    /** Use (and remove) a recovery code. */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code  = strtoupper(trim($code));
        $codes = $this->recoveryCodes($user);

        $index = array_search($code, $codes, true);
        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->forceFill(['two_factor_recovery_codes' => json_encode(array_values($codes))])->save();

        return true;
    }

    /** @return string[] */
    public function recoveryCodes(User $user): array
    {
        return $user->two_factor_recovery_codes
            ? (json_decode($user->two_factor_recovery_codes, true) ?: [])
            : [];
    }

    /** QR code for the authenticator app, as inline SVG. */
    public function qrCodeSvg(User $user): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd()));

        $svg = $writer->writeString($this->otpauthUrl($user));

        // Drop the XML declaration so it can be placed inline in the page.
        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    public function otpauthUrl(User $user): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'Staffee'),
            $user->email,
            (string) $user->two_factor_secret
        );
    }

    /** "ABCD EFGH ..." — easier to type than one long string. */
    public function formattedSecret(User $user): string
    {
        return trim(chunk_split((string) $user->two_factor_secret, 4, ' '));
    }

    /** @return string[] e.g. "K7Q2-M9XD" */
    private function generateRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4)))
            ->all();
    }
}
