<?php

namespace App\Traits;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create, update and delete of a model in the audit trail.
 *
 * A model can list extra columns to leave out with
 *   protected array $auditExclude = ['some_column'];
 * and can hide sensitive values with redactForAudit().
 */
trait Auditable
{
    /** Columns that change on every save and say nothing useful. */
    private static array $auditAlwaysIgnore = ['created_at', 'updated_at'];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $model->writeAudit('created', [], $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $changed = $model->auditableAttributes($model->getChanges());
            if ($changed === []) {
                return; // only timestamps or ignored columns changed
            }

            $old = array_intersect_key($model->getOriginal(), $changed);
            $model->writeAudit('updated', $model->auditableAttributes($old), $changed);
        });

        static::deleted(function (Model $model) {
            $model->writeAudit('deleted', $model->auditableAttributes($model->getAttributes()), []);
        });
    }

    /** Hook for models with values that must not be stored in plain text. */
    protected function redactForAudit(array $values): array
    {
        return $values;
    }

    private function auditableAttributes(array $values): array
    {
        $ignore = array_merge(self::$auditAlwaysIgnore, $this->auditExclude ?? []);

        return $this->redactForAudit(array_diff_key($values, array_flip($ignore)));
    }

    private function writeAudit(string $event, array $old, array $new): void
    {
        app(AuditLogger::class)->record($event, $this, $old, $new);
    }
}
