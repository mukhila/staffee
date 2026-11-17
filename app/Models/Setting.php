<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use \App\Traits\Auditable;
    protected $fillable = ['key', 'value'];

    /** Keep secrets such as the SMTP password out of the audit trail. */
    protected function redactForAudit(array $values): array
    {
        if (array_key_exists('value', $values) && preg_match('/password|secret|token|key$/i', (string) $this->key)) {
            $values['value'] = \App\Services\AuditLogger::REDACTED;
        }

        return $values;
    }
}
