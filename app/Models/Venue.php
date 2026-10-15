<?php

namespace App\Models;

use App\Enums\VenueKind;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use Auditable, Publishable;

    protected $fillable = [
        'kind', 'name', 'address', 'directions', 'latitude', 'longitude', 'maps_url', 'is_published', 'sort_order',
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

        return $this->hasCoordinates()
            ? "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}"
            : null;
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
