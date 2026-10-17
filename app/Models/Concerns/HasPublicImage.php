<?php

namespace App\Models\Concerns;

use App\Support\ImageOptimizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imagen pública opcional guardada en el disco "public" con nombre aleatorio.
 * El modelo define la columna en $imageColumn y, opcionalmente:
 *  - $imageBounds = [ancho, alto] máximos al guardar (por defecto 1600 × 1600);
 *  - $imageLossless = true para imágenes que no admiten pérdida (códigos QR).
 *
 * Al subirla se optimiza (ver ImageOptimizer): mismo aspecto, mucho menos peso.
 *
 * @mixin Model
 */
trait HasPublicImage
{
    public static function bootHasPublicImage(): void
    {
        static::deleted(fn (self $model) => $model->deleteImageFile());
    }

    public function imageUrl(): ?string
    {
        $path = $this->getAttribute($this->imageColumn);

        // asset() usa el host de la petición actual: funciona aunque APP_URL no coincida
        // (p. ej. XAMPP en un subdirectorio).
        return $path ? asset('storage/'.$path) : null;
    }

    public function replaceImage(UploadedFile $file): void
    {
        $this->swapImagePath($this->storeOptimized($file->getRealPath()) ?? $file->store($this->getTable(), 'public'));
    }

    /**
     * Optimiza la imagen ya guardada (para las subidas antes de existir la
     * optimización). Devuelve los bytes ahorrados, o null si no se cambió.
     */
    public function optimizeStoredImage(): ?int
    {
        $path = $this->getAttribute($this->imageColumn);
        $disk = Storage::disk('public');

        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        $before = $disk->size($path);
        $newPath = $this->storeOptimized($disk->path($path), $before);

        if (! $newPath) {
            return null;
        }

        $saved = $before - $disk->size($newPath);
        $this->swapImagePath($newPath);

        return $saved;
    }

    /**
     * Guarda la versión optimizada y devuelve su ruta. null si GD no puede
     * procesarla o si no pesaría menos que $mustBeSmallerThan.
     */
    protected function storeOptimized(string $sourcePath, ?int $mustBeSmallerThan = null): ?string
    {
        [$maxWidth, $maxHeight] = $this->imageBounds ?? [1600, 1600];

        $result = app(ImageOptimizer::class)->optimize($sourcePath, $maxWidth, $maxHeight, $this->imageLossless ?? false);

        if (! $result || ($mustBeSmallerThan !== null && strlen($result['contents']) >= $mustBeSmallerThan)) {
            return null;
        }

        $path = $this->getTable().'/'.Str::random(40).'.'.$result['extension'];

        return Storage::disk('public')->put($path, $result['contents']) ? $path : null;
    }

    private function swapImagePath(string $newPath): void
    {
        $previous = $this->getAttribute($this->imageColumn);

        $this->setAttribute($this->imageColumn, $newPath);
        $this->save();

        if ($previous && $previous !== $newPath) {
            Storage::disk('public')->delete($previous);
        }
    }

    public function removeImage(): void
    {
        $this->deleteImageFile();
        $this->setAttribute($this->imageColumn, null);
        $this->save();
    }

    protected function deleteImageFile(): void
    {
        $path = $this->getAttribute($this->imageColumn);

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
