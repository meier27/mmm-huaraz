<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ScopedToEvent;
use App\Livewire\Forms\DishOfferingForm;
use App\Models\Dish;
use App\Models\DishOffering;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Ofertas y stock')]
class DishOfferingManager extends ContentManager
{
    use ScopedToEvent;

    public DishOfferingForm $form;

    protected function module(): string
    {
        return 'food';
    }

    protected function modelClass(): string
    {
        return DishOffering::class;
    }

    public function toggleActive(int $id): void
    {
        $offering = $this->findRecord($id);
        $offering->is_active = ! $offering->is_active;
        $offering->save();
    }

    public function delete(int $id): void
    {
        if ($this->findRecord($id)->items()->exists()) {
            $this->addError('delete', 'Esta oferta tiene reservas: desactívala en lugar de eliminarla.');

            return;
        }

        parent::delete($id);
    }

    public function render(): View
    {
        $offerings = $this->query()->with('dish:id,name')->orderBy('serve_date')->orderBy('slot_start')->get();

        return view('livewire.admin.dish-offering-manager', [
            'groups' => $offerings->groupBy(fn ($o) => $o->serve_date->toDateString()),
            'dishes' => Dish::query()->forEvent($this->eventId)->ordered()->get(['id', 'name']),
        ]);
    }
}
