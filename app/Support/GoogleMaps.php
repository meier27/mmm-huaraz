<?php

namespace App\Support;

/**
 * Enlaces y mapas incrustados de Google Maps sin clave de API.
 * Con coordenadas se marca el punto exacto; si no, se busca la dirección.
 */
class GoogleMaps
{
    public static function query(float|string|null $latitude, float|string|null $longitude, ?string $address): ?string
    {
        if ($latitude !== null && $latitude !== '' && $longitude !== null && $longitude !== '') {
            return (float) $latitude.','.(float) $longitude;
        }

        return filled($address) ? trim($address) : null;
    }

    public static function embedUrl(?string $query, int $zoom = 16): ?string
    {
        return $query ? 'https://maps.google.com/maps?q='.rawurlencode($query)."&z={$zoom}&output=embed" : null;
    }

    public static function link(?string $query): ?string
    {
        return $query ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query) : null;
    }

    /**
     * Coordenadas de un enlace de Google Maps pegado por el equipo
     * («…/@-9.52,-77.53,17z», «?q=-9.52,-77.53», «!3d-9.52!4d-77.53»).
     *
     * @return array{0: float, 1: float}|null
     */
    public static function coordinatesFromUrl(?string $url): ?array
    {
        if (blank($url)) {
            return null;
        }

        $url = rawurldecode($url);
        $number = '(-?\d{1,3}\.\d+)';

        foreach (["/!3d{$number}!4d{$number}/", "/@{$number},{$number}/", "/[?&](?:q|query|ll|destination)={$number},\s*{$number}/"] as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                [$lat, $lng] = [(float) $m[1], (float) $m[2]];

                if (abs($lat) <= 90 && abs($lng) <= 180) {
                    return [$lat, $lng];
                }
            }
        }

        return null;
    }
}
