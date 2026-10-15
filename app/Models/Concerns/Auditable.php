<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra en audit_logs las altas, cambios y bajas del modelo.
 * Los atributos ocultos ($hidden) nunca se guardan en claro.
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLog::record('created', $model, [], $model->auditValues($model->getAttributes())));

        static::updated(function (Model $model) {
            $changes = $model->auditValues($model->getChanges());

            if ($changes !== []) {
                AuditLog::record('updated', $model, $model->auditValues(array_intersect_key($model->getOriginal(), $changes)), $changes);
            }
        });

        static::deleted(fn (Model $model) => AuditLog::record('deleted', $model, $model->auditValues($model->getAttributes())));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditValues(array $attributes): array
    {
        $values = array_diff_key($attributes, array_flip(['id', 'created_at', 'updated_at', 'remember_token']));

        foreach ($this->getHidden() as $hidden) {
            if (array_key_exists($hidden, $values)) {
                $values[$hidden] = '[oculto]';
            }
        }

        return array_map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value, $values);
    }
}
