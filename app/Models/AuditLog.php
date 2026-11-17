<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One entry in the audit trail. Rows are only ever inserted, never edited.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const EVENT_LABELS = [
        'created'              => 'Created',
        'updated'              => 'Updated',
        'deleted'              => 'Deleted',
        'login'                => 'Signed in',
        'logout'               => 'Signed out',
        'login_failed'         => 'Failed sign-in',
        'password_reset'       => 'Password reset',
        'two_factor_enabled'   => 'Two-factor turned on',
        'two_factor_disabled'  => 'Two-factor turned off',
        'two_factor_reset'     => 'Two-factor reset by admin',
        'two_factor_failed'    => 'Failed two-factor code',
        'recovery_code_used'   => 'Recovery code used',
        'recovery_codes_regenerated' => 'Recovery codes regenerated',
        'api_token_created'    => 'API token created',
        'api_token_revoked'    => 'API token revoked',
        'import'               => 'CSV import',
    ];

    public const EVENT_COLORS = [
        'created'             => 'success',
        'updated'             => 'info',
        'deleted'             => 'danger',
        'login'               => 'primary',
        'logout'              => 'secondary',
        'login_failed'        => 'warning',
        'two_factor_failed'   => 'warning',
        'two_factor_disabled' => 'warning',
        'two_factor_reset'    => 'warning',
    ];

    protected $fillable = [
        'user_id', 'event', 'auditable_type', 'auditable_id', 'description',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'url',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENT_LABELS[$this->event] ?? ucfirst(str_replace('_', ' ', $this->event));
    }

    public function getEventColorAttribute(): string
    {
        return self::EVENT_COLORS[$this->event] ?? 'secondary';
    }

    /** "Leave Request" from App\Models\LeaveRequest. */
    public function getRecordLabelAttribute(): ?string
    {
        if (!$this->auditable_type) {
            return null;
        }

        return \Illuminate\Support\Str::headline(class_basename($this->auditable_type));
    }

    /**
     * Field-by-field changes for display: [field => [old, new]].
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function changes(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        $rows = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            $rows[$field] = [$old[$field] ?? null, $new[$field] ?? null];
        }

        return $rows;
    }
}
