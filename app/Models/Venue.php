<?php

namespace App\Models;

use App\Enums\VenueKind;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToEvent;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\Publishable;
use App\Support\GoogleMaps;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use Auditable, BelongsToEvent, HasPublicImage, Publishable;

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [1600, 1200];

    protected $fillable = [
        'kind', 'name', 'address', 'directions', 'latitude', 'longitude', 'maps_url', 'image_alt', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'kind' => VenueKind::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Enlace confirmado por la organización o, si hay coordenadas, búsqueda por coordenadas.
     */
    public function mapsLink(): ?string
    {
        if ($this->maps_url) {
            return $this->maps_url;
        }

        return GoogleMaps::link($this->mapQuery());
    }

    /** Mapa incrustado: punto exacto si hay coordenadas; si no, la dirección. */
    public function mapEmbedUrl(): ?string
    {
        return GoogleMaps::embedUrl($this->mapQuery());
    }

    private function mapQuery(): ?string
    {
        return GoogleMaps::query($this->latitude, $this->longitude, $this->address);
    }

    public function openStreetMapEmbedUrl(): ?string
    {
        if (! $this->hasCoordinates()) {
            return null;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;
        $bbox = implode(',', [$lng - 0.006, $lat - 0.004, $lng + 0.006, $lat + 0.004]);

        return "https://www.openstreetmap.org/export/embed.html?bbox={$bbox}&layer=mapnik&marker={$lat},{$lng}";
    }
}
