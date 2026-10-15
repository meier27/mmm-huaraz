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
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Configuración vigente. Si aún no se ha sembrado, usa los datos
     * verificados de config/convention.php sin guardarlos.
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->first()?->getAttributes());

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
