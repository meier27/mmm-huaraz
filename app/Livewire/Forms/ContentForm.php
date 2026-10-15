<?php

namespace App\Livewire\Forms;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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
        $data = Arr::except($this->validate(), $this->except);

        return array_map(fn ($value) => $value === '' ? null : $value, $data);
    }
}
