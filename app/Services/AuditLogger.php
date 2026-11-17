<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes entries to the audit trail.
 *
 * A failure to write an audit row is logged but never breaks the action
 * being audited.
 */
class AuditLogger
{
    /** Never stored, whatever the model. */
    public const ALWAYS_REDACTED = [
        'password', 'remember_token', 'agent_token',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    public const REDACTED = '[hidden]';

    /** Longest value kept for any one field. */
    private const MAX_VALUE_LENGTH = 1000;

    public function record(
        string $event,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?string $description = null,
        ?int $actorId = null,
    ): ?AuditLog {
        try {
            $request = app()->runningInConsole() && !app()->runningUnitTests() ? null : request();

            return AuditLog::create([
                'user_id'        => $actorId ?? Auth::id(),
                'event'          => $event,
                'auditable_type' => $subject ? $subject->getMorphClass() : null,
                'auditable_id'   => $subject?->getKey(),
                'description'    => $description ? mb_substr($description, 0, 255) : null,
                'old_values'     => $old ? $this->clean($old) : null,
                'new_values'     => $new ? $this->clean($new) : null,
                'ip_address'     => $request?->ip(),
                'user_agent'     => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
                'url'            => $request ? mb_substr($request->fullUrl(), 0, 500) : null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Audit log write failed: ' . $e->getMessage(), ['event' => $event]);

            return null;
        }
    }

    /** Redact secrets and trim long values. */
    private function clean(array $values): array
    {
        $out = [];

        foreach ($values as $key => $value) {
            if (in_array($key, self::ALWAYS_REDACTED, true)) {
                $out[$key] = self::REDACTED;
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            if (is_string($value) && mb_strlen($value) > self::MAX_VALUE_LENGTH) {
                $value = mb_substr($value, 0, self::MAX_VALUE_LENGTH) . '…';
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
