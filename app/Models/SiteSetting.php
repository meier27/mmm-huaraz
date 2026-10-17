<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\CachedSingleton;
use App\Support\GoogleMaps;
use Illuminate\Database\Eloquent\Model;

/**
 * Identidad, textos institucionales y contacto del MMM Huaraz (una sola fila).
 */
class SiteSetting extends Model
{
    use Auditable, CachedSingleton;

    protected $fillable = [
        'church_name', 'tagline', 'welcome_text', 'history', 'mission', 'vision', 'service_schedule',
        'address', 'latitude', 'longitude',
        'contact_phone', 'contact_whatsapp', 'contact_email',
        'facebook_url', 'instagram_url', 'youtube_url', 'tiktok_url',
        'bible_version', 'bethel_channel_id',
    ];

    public static function defaults(): array
    {
        return [
            'church_name' => 'Movimiento Misionero Mundial – Huaraz',
            'bible_version' => config('bible.default_version'),
            'bethel_channel_id' => config('bible.bethel_channel_id'),
        ];
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** Mapa del templo: punto exacto si hay coordenadas; si no, la dirección. */
    public function mapEmbedUrl(): ?string
    {
        return GoogleMaps::embedUrl(GoogleMaps::query($this->latitude, $this->longitude, $this->address));
    }

    public function mapsLink(): ?string
    {
        return GoogleMaps::link(GoogleMaps::query($this->latitude, $this->longitude, $this->address));
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->contact_whatsapp);

        return $digits !== '' ? "https://wa.me/{$digits}" : null;
    }

    public function hasContact(): bool
    {
        return (bool) ($this->contact_phone || $this->contact_whatsapp || $this->contact_email);
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

    /** @return list<string> */
    public function scheduleLines(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->service_schedule))));
    }

    public function bibleVersion(): string
    {
        $version = $this->bible_version ?: config('bible.default_version');

        return array_key_exists($version, config('bible.versions')) ? $version : config('bible.default_version');
    }
}
