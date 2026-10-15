<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class EventSetting extends Model
{
    use Auditable;

    private const CACHE_KEY = 'event_settings';

    protected $fillable = [
        'organization', 'name', 'theme', 'city', 'starts_on', 'ends_on', 'summary',
        'contact_phone', 'contact_whatsapp', 'contact_email',
        'facebook_url', 'instagram_url', 'youtube_url', 'tiktok_url',
        'reservations_open', 'reservation_instructions', 'deposit_min_percent',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'reservations_open' => 'boolean',
            'deposit_min_percent' => 'integer',
        ];
    }

    /**
     * Adelanto mínimo exigido para un total dado (0.00 si no hay mínimo configurado).
     */
    public function minimumDeposit(string $total): string
    {
        if (! $this->deposit_min_percent) {
            return '0.00';
        }

        // Redondeo hacia arriba al céntimo.
        $raw = bcdiv(bcmul($total, (string) $this->deposit_min_percent, 4), '100', 4);
        $cents = (int) ceil((float) bcmul($raw, '100', 4));

        return number_format($cents / 100, 2, '.', '');
    }

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => Cache::forget($setting->cacheKey()));
    }

    /**
     * La clave incluye la lista de columnas: si una migración añade campos,
     * la caché anterior (sin esos campos) se ignora automáticamente.
     */
    private function cacheKey(): string
    {
        return self::CACHE_KEY.':'.md5(implode(',', $this->getFillable()));
    }

    /**
     * Configuración vigente. Si aún no se ha sembrado, usa los datos
     * verificados de config/convention.php sin guardarlos.
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever((new static)->cacheKey(), fn () => static::query()->first()?->getAttributes());

        if ($attributes === null) {
            return new static(static::defaults());
        }

        $setting = (new static)->setRawAttributes($attributes, true);
        $setting->exists = true;

        return $setting;
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'organization' => config('convention.organization'),
            'name' => config('convention.name'),
            'theme' => config('convention.theme'),
            'city' => config('convention.city'),
            'starts_on' => config('convention.starts_at'),
            'ends_on' => config('convention.ends_at'),
        ];
    }

    public function dateRangeLabel(): string
    {
        $start = $this->starts_on;
        $end = $this->ends_on;

        if ($start->isSameMonth($end)) {
            return $start->day.' al '.$end->translatedFormat('j \d\e F \d\e Y');
        }

        return $start->translatedFormat('j \d\e F').' al '.$end->translatedFormat('j \d\e F \d\e Y');
    }

    /**
     * Estado relativo a hoy, calculado en el servidor (sin JavaScript).
     */
    public function countdownLabel(?Carbon $today = null): string
    {
        $today = ($today ?? now())->startOfDay();

        if ($today->lt($this->starts_on)) {
            $days = (int) $today->diffInDays($this->starts_on);

            return $days === 1 ? 'Falta 1 día' : "Faltan {$days} días";
        }

        return $today->lte($this->ends_on) ? 'La convención está en curso' : 'La convención ha finalizado';
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->contact_whatsapp);

        return $digits !== '' ? "https://wa.me/{$digits}" : null;
    }

    /**
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return array_filter([
            'Facebook' => $this->facebook_url,
            'Instagram' => $this->instagram_url,
            'YouTube' => $this->youtube_url,
            'TikTok' => $this->tiktok_url,
        ]);
    }
}
