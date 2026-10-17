<?php

namespace App\Livewire\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadController;

/**
 * Subida temporal de Livewire con el nombre original acortado.
 *
 * Livewire guarda el archivo temporal con el nombre original codificado en
 * base64 dentro del suyo. Con fotos de nombre largo (p. ej. «WhatsApp Image
 * 2026-10-16 at …») el resultado supera los 255 caracteres que admite Windows,
 * el guardado falla en silencio y al validar aparece un error 500
 * («Unable to retrieve the file_size … livewire-tmp/livewire-tmp»).
 */
class ShortNameFileUploadController extends FileUploadController
{
    /** Caracteres del nombre original que se conservan (sin la extensión). */
    private const MAX_NAME = 40;

    public function validateAndStore($files, $disk)
    {
        $files = collect($files)->map(fn ($file) => $file instanceof UploadedFile ? $this->shorten($file) : $file)->all();

        return parent::validateAndStore($files, $disk);
    }

    private function shorten(UploadedFile $file): UploadedFile
    {
        $extension = Str::lower(Str::limit($file->getClientOriginalExtension(), 5, ''));
        $name = Str::limit(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), self::MAX_NAME, '') ?: 'archivo';

        return new UploadedFile(
            $file->getRealPath(),
            $name.($extension !== '' ? '.'.$extension : ''),
            $file->getClientMimeType(),
            $file->getError(),
            // El archivo ya es una subida HTTP válida: solo cambia su nombre.
            test: true,
        );
    }
}
