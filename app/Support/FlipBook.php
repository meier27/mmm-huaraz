<?php

namespace App\Support;

/**
 * Libros de FlipHTML5 (p. ej. el Doctrinario del MMM). Solo se aceptan sus
 * enlaces https: nunca se guarda ni se muestra el HTML que se pegue.
 */
class FlipBook
{
    private const PATTERN = '#^https://online\.fliphtml5\.com/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+/?$#';

    /**
     * Enlace limpio a partir de lo que se pegue: la dirección o el código
     * <iframe> completo que da FlipHTML5. Null si no es un libro de FlipHTML5.
     */
    public static function url(?string $input): ?string
    {
        $input = trim((string) $input);

        if (preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $input, $m)) {
            $input = trim($m[1]);
        }

        $input = strtok($input, '?#') ?: '';

        return preg_match(self::PATTERN, $input) ? rtrim($input, '/').'/' : null;
    }
}
