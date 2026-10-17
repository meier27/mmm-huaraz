<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * Tabla de configuración con una sola fila, cacheada como atributos planos.
 * La clave incluye la lista de columnas: si una migración añade campos, la
 * caché anterior (sin esos campos) se ignora automáticamente.
 */
trait CachedSingleton
{
    public static function bootCachedSingleton(): void
    {
        static::saved(fn (self $setting) => Cache::forget($setting->cacheKey()));
    }

    public static function current(): static
    {
        $attributes = Cache::rememberForever((new static)->cacheKey(), fn () => static::query()->first()?->getAttributes());

        if ($attributes === null) {
            return new static(static::defaults());
        }

        $setting = (new static)->setRawAttributes($attributes, true);
        $setting->exists = true;

        return $setting;
    }

    /** Valores provisionales mientras la fila no existe. */
    abstract public static function defaults(): array;

    private function cacheKey(): string
    {
        return $this->getTable().':'.md5(implode(',', $this->getFillable()));
    }
}
