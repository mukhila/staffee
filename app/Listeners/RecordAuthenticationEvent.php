<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

/**
 * Puts sign-ins, sign-outs, failed sign-ins and password resets in the audit trail.
 */
class RecordAuthenticationEvent
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Login|Logout|Failed|PasswordReset $event): void
    {
        match (true) {
            $event instanceof Login => $this->audit->record(
                'login', $event->user, [], [], $event->remember ? 'Signed in (remember me)' : 'Signed in', $event->user->getAuthIdentifier()
            ),
            $event instanceof Logout => $event->user
                ? $this->audit->record('logout', $event->user, [], [], 'Signed out', $event->user->getAuthIdentifier())
                : null,
            $event instanceof Failed => $this->audit->record(
                'login_failed',
                $event->user,
                [],
                // Only the email; the attempted password is never stored.
                ['email' => (string) ($event->credentials['email'] ?? '')],
                $event->user ? 'Wrong password' : 'Unknown email',
                $event->user?->getAuthIdentifier(),
            ),
            $event instanceof PasswordReset => $this->audit->record(
                'password_reset', $event->user, [], [], 'Password reset by email link', $event->user->getAuthIdentifier()
            ),
        };
    }
}
