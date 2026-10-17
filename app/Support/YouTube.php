<?php

namespace App\Support;

class YouTube
{
    /**
     * Extrae el identificador de vídeo de las URLs habituales de YouTube.
     * Devuelve null si la URL no es de YouTube o no contiene un id válido.
     */
    public static function videoId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url(trim($url));
        $host = strtolower(preg_replace('/^www\.|^m\./', '', $parts['host'] ?? ''));

        if (! in_array($host, ['youtube.com', 'youtu.be', 'youtube-nocookie.com'], true)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $candidate = match (true) {
            $host === 'youtu.be' => ltrim($path, '/'),
            isset($query['v']) => $query['v'],
            (bool) preg_match('#^/(embed|live|shorts|v)/([^/]+)#', $path, $m) => $m[2],
            default => null,
        };

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }

    public static function embedUrl(string $videoId): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$videoId.'?rel=0';
    }

    public static function liveEmbedUrl(string $channelId): string
    {
        return 'https://www.youtube-nocookie.com/embed/live_stream?channel='.$channelId;
    }

    public static function watchUrl(string $videoId): string
    {
        return 'https://www.youtube.com/watch?v='.$videoId;
    }
}
