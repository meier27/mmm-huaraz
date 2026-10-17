<?php

namespace App\Support;

use GdImage;

/**
 * Optimiza las imágenes subidas con GD (sin dependencias nuevas):
 * corrige la orientación de las fotos del móvil, reduce las que superan el
 * tamaño al que se muestran y las guarda en WebP de alta calidad. Al volver a
 * codificar se descartan los metadatos EXIF (incluida la ubicación GPS).
 */
class ImageOptimizer
{
    /** Calidad WebP: sin pérdida apreciable a simple vista. */
    public const QUALITY = 85;

    /**
     * @return array{contents: string, extension: string}|null null si no se puede
     *                                                           procesar (se guarda el original)
     */
    public function optimize(string $path, int $maxWidth, int $maxHeight, bool $lossless = false): ?array
    {
        $info = @getimagesize($path);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image instanceof GdImage) {
            return null;
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $image = $this->applyExifOrientation($image, $path);
        }

        $image = $this->fitWithin($image, $maxWidth, $maxHeight);

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $encoded = imagewebp($image, null, $lossless ? IMG_WEBP_LOSSLESS : self::QUALITY);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        if (! $encoded || $contents === '') {
            return null;
        }

        return ['contents' => $contents, 'extension' => 'webp'];
    }

    /** Reduce (nunca amplía) manteniendo la proporción. */
    private function fitWithin(GdImage $image, int $maxWidth, int $maxHeight): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxWidth / $width, $maxHeight / $height);

        if ($scale >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /** Las fotos de móvil suelen venir «de lado» con la rotación en el EXIF. */
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated instanceof GdImage) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
