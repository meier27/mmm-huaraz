<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Los registros de auditoría son de solo inserción.
        static::updating(fn () => throw new LogicException('Los registros de auditoría no se pueden modificar.'));
        static::deleting(fn () => throw new LogicException('Los registros de auditoría no se pueden eliminar.'));
    }

    public static function record(string $action, Model $model, array $old = [], array $new = []): self
    {
        $request = app()->runningInConsole() ? null : request();

        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditableLabel(): string
    {
        return class_basename($this->auditable_type).' #'.$this->auditable_id;
    }
}
