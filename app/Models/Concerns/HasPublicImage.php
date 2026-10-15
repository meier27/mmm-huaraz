<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen pública opcional guardada en el disco "public" con nombre aleatorio.
 * El modelo define la columna en $imageColumn.
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
        $previous = $this->getAttribute($this->imageColumn);

        $this->setAttribute($this->imageColumn, $file->store($this->getTable(), 'public'));
        $this->save();

        if ($previous) {
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
