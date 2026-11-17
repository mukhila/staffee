<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user      = User::factory()->create(['role' => 'staff', 'password' => 'secret-password']);
        $this->google2fa = app(Google2FA::class);
    }

    /** Turn two-factor on for the user; returns the recovery codes. */
    private function enableTwoFactor(User $user): array
    {
        $service = app(TwoFactorService::class);
        $service->beginSetup($user);
        $codes = $service->confirm($user->fresh(), $this->currentCode($user->fresh()));
        Cache::flush(); // let the same time step be used again in the test that follows

        return $codes;
    }

    private function currentCode(User $user): string
    {
        return $this->google2fa->getCurrentOtp($user->fresh()->two_factor_secret);
    }

    private function signInWithPassword(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', ['email' => $this->user->email, 'password' => 'secret-password']);
    }

    // ── Setup ─────────────────────────────────────────────────────────────────

    public function test_full_setup_flow(): void
    {
        $this->actingAs($this->user);

        $this->get(route('profile.security'))->assertOk()->assertSee('Set up two-step verification');

        $this->post(route('profile.security.two-factor.enable'))->assertRedirect(route('profile.security'));
        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_secret);
        $this->assertFalse($this->user->hasTwoFactorEnabled());

        $this->get(route('profile.security'))->assertOk()->assertSee('<svg', false)->assertSee('Enter the 6-digit code');

        $this->post(route('profile.security.two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());

        $this->post(route('profile.security.two-factor.confirm'), ['code' => $this->currentCode($this->user)])
            ->assertRedirect(route('profile.security'))
            ->assertSessionHas('recovery_codes');

        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());
        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT, app(TwoFactorService::class)->recoveryCodes($this->user->fresh()));
        $this->assertNotNull(AuditLog::where('event', 'two_factor_enabled')->first());
    }

    public function test_secret_is_encrypted_at_rest(): void
    {
        $this->enableTwoFactor($this->user);

        $raw = \DB::table('users')->where('id', $this->user->id)->value('two_factor_secret');
        $this->assertNotSame($this->user->fresh()->two_factor_secret, $raw);
        $this->assertStringNotContainsString($this->user->fresh()->two_factor_secret, (string) $raw);
    }

    public function test_cancel_setup_clears_unconfirmed_secret(): void
    {
        $this->actingAs($this->user);
        $this->post(route('profile.security.two-factor.enable'));
        $this->post(route('profile.security.two-factor.cancel'));

        $this->assertNull($this->user->fresh()->two_factor_secret);
    }

    // ── Sign-in ───────────────────────────────────────────────────────────────

    public function test_sign_in_without_two_factor_is_unchanged(): void
    {
        $this->signInWithPassword()->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_correct_password_asks_for_code_and_does_not_sign_in_yet(): void
    {
        $this->enableTwoFactor($this->user);

        $this->signInWithPassword()->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Two-step verification');
    }

    public function test_valid_code_signs_in(): void
    {
        $this->enableTwoFactor($this->user);
        $this->signInWithPassword();

        $this->post(route('two-factor.challenge'), ['code' => $this->currentCode($this->user)])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->user);
        $this->assertNotNull(AuditLog::where('event', 'login')->where('auditable_id', $this->user->id)->first());
    }

    public function test_wrong_code_is_refused_and_recorded(): void
    {
        $this->enableTwoFactor($this->user);
        $this->signInWithPassword();

        $this->post(route('two-factor.challenge'), ['code' => '123456'])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertNotNull(AuditLog::where('event', 'two_factor_failed')->first());
    }

    public function test_code_cannot_be_reused(): void
    {
        $this->enableTwoFactor($this->user);
        $code = $this->currentCode($this->user);

        $this->signInWithPassword();
        $this->post(route('two-factor.challenge'), ['code' => $code]);
        $this->assertAuthenticatedAs($this->user);
        $this->post('/logout');

        $this->signInWithPassword();
        $this->post(route('two-factor.challenge'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_attempts_are_rate_limited(): void
    {
        $this->enableTwoFactor($this->user);
        $this->signInWithPassword();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.challenge'), ['code' => '000000']);
        }

        $response = $this->post(route('two-factor.challenge'), ['code' => $this->currentCode($this->user)]);
        $response->assertSessionHasErrors('code');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('code'));
        $this->assertGuest();
    }

    public function test_recovery_code_signs_in_once(): void
    {
        $codes = $this->enableTwoFactor($this->user);

        $this->signInWithPassword();
        $this->post(route('two-factor.challenge'), ['recovery_code' => strtolower($codes[0])])
            ->assertRedirect(route('profile.security'))
            ->assertSessionHas('warning');
        $this->assertAuthenticatedAs($this->user);
        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT - 1, app(TwoFactorService::class)->recoveryCodes($this->user->fresh()));
        $this->post('/logout');

        $this->signInWithPassword();
        $this->post(route('two-factor.challenge'), ['recovery_code' => $codes[0]])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_challenge_without_password_step_redirects_to_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.challenge'), ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_challenge_expires(): void
    {
        $this->enableTwoFactor($this->user);
        $this->signInWithPassword();

        $this->travel(6)->minutes();

        $this->post(route('two-factor.challenge'), ['code' => $this->currentCode($this->user)])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ── Manage ────────────────────────────────────────────────────────────────

    public function test_disable_requires_current_password(): void
    {
        $this->enableTwoFactor($this->user);
        $this->actingAs($this->user->fresh());

        $this->delete(route('profile.security.two-factor.disable'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());

        $this->delete(route('profile.security.two-factor.disable'), ['password' => 'secret-password'])->assertSessionHas('success');
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
    }

    public function test_regenerating_recovery_codes_invalidates_old_ones(): void
    {
        $old = $this->enableTwoFactor($this->user);
        $this->actingAs($this->user->fresh());

        $this->post(route('profile.security.recovery-codes'), ['password' => 'secret-password'])->assertSessionHas('recovery_codes');

        $new = app(TwoFactorService::class)->recoveryCodes($this->user->fresh());
        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT, $new);
        $this->assertEmpty(array_intersect($old, $new));
    }

    public function test_admin_can_reset_and_staff_cannot(): void
    {
        $this->enableTwoFactor($this->user);
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'staff']);

        $this->actingAs($other)->delete(route('admin.staff.two-factor.reset', $this->user))->assertForbidden();
        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($admin)->delete(route('admin.staff.two-factor.reset', $this->user))->assertSessionHas('success');
        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
        $this->assertSame($admin->id, AuditLog::where('event', 'two_factor_reset')->first()->user_id);
    }
}
