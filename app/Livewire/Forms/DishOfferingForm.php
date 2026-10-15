<?php

namespace App\Livewire\Forms;

use App\Models\DishOffering;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DishOfferingForm extends ContentForm
{
    public int|string|null $dish_id = '';

    public string $serve_date = '';

    public string $slot_start = '';

    public ?string $slot_end = '';

    public int|float|string $price = '';

    public int|string $stock = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        $editingId = $this->getComponent()->editingId;
        $reserved = $editingId ? (int) DishOffering::whereKey($editingId)->value('reserved') : 0;

        return [
            'dish_id' => ['required', 'integer', 'exists:dishes,id'],
            'serve_date' => ['required', 'date_format:Y-m-d'],
            'slot_start' => [
                'required', 'date_format:H:i,H:i:s',
                Rule::unique('dish_offerings')->where(fn ($q) => $q->where('dish_id', $this->dish_id)->where('serve_date', $this->serve_date))->ignore($editingId),
            ],
            'slot_end' => ['nullable', 'date_format:H:i,H:i:s', 'after:slot_start'],
            'price' => ['required', 'decimal:0,2', 'min:0.10', 'max:9999.99'],
            // El stock no puede quedar por debajo de lo ya reservado.
            'stock' => ['required', 'integer', "min:$reserved", 'max:100000'],
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'stock.min' => 'El stock no puede ser menor que las porciones ya reservadas (:min).',
            'slot_start.unique' => 'Ese plato ya tiene una oferta en esa fecha y hora.',
        ];
    }

    public function fillFrom(Model $record): void
    {
        parent::fillFrom($record);

        $this->slot_start = substr($this->slot_start, 0, 5);
        $this->slot_end = $this->slot_end ? substr($this->slot_end, 0, 5) : '';
    }
}
