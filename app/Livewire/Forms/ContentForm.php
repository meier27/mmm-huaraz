<?php

namespace App\Livewire\Forms;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/**
 * Formulario cuyas propiedades coinciden con columnas del modelo.
 * Las propiedades que no son columnas (p. ej. archivos) van en $except.
 */
abstract class ContentForm extends Form
{
    /** @var list<string> */
    protected array $except = [];

    public function fillFrom(Model $record): void
    {
        $values = [];

        foreach (array_keys(Arr::except($this->all(), $this->except)) as $key) {
            $value = $record->getAttribute($key);

            // Un nulo (p. ej. columna con valor por defecto en un modelo aún no
            // recargado) no pisa una propiedad que no lo admite: queda su valor inicial.
            if ($value === null && ! (new \ReflectionProperty($this, $key))->getType()?->allowsNull()) {
                continue;
            }

            $values[$key] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof CarbonInterface => $value->format('Y-m-d'),
                default => $value,
            };
        }

        $this->fill($values);
    }

    /**
     * Valida y devuelve los atributos listos para el modelo ('' pasa a null).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        $this->rejectBrokenUploads();

        $data = Arr::except($this->validate(), $this->except);

        return array_map(fn ($value) => $value === '' ? null : $value, $data);
    }

    /**
     * Una subida temporal que no llegó a guardarse (o ya se limpió) no tiene
     * archivo: se avisa en el campo en lugar de fallar al leer su tamaño.
     */
    private function rejectBrokenUploads(): void
    {
        foreach ($this->all() as $key => $value) {
            // fileExists (no exists): una ruta vacía apunta a la carpeta temporal, que sí existe.
            $missing = $value instanceof TemporaryUploadedFile && ! Storage::disk(FileUploadConfiguration::disk())
                ->fileExists(FileUploadConfiguration::path($value->getFilename(), false));

            if ($missing) {
                $this->reset($key);

                throw ValidationException::withMessages([
                    $this->getPropertyName().'.'.$key => 'No se pudo recibir el archivo. Vuelve a seleccionarlo.',
                ]);
            }
        }
    }
}
