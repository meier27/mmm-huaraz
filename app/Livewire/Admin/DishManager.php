<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ScopedToEvent;
use App\Livewire\Forms\DishForm;
use App\Models\Dish;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Platos')]
class DishManager extends ContentManager
{
    use ScopedToEvent, WithFileUploads;

    public DishForm $form;

    protected function module(): string
    {
        return 'food';
    }

    protected function modelClass(): string
    {
        return Dish::class;
    }

    /** @param Dish $record */
    protected function afterSave(Model $record): void
    {
        if ($this->form->image) {
            $record->replaceImage($this->form->image);
        }
    }

    public function delete(int $id): void
    {
        if ($this->findRecord($id)->offerings()->exists()) {
            $this->addError('delete', 'Este plato tiene ofertas registradas: despublícalo en lugar de eliminarlo.');

            return;
        }

        parent::delete($id);
    }

    public function removeImage(int $id): void
    {
        $this->findRecord($id)->removeImage();
    }

    public function render(): View
    {
        return view('livewire.admin.dish-manager', [
            'records' => $this->query()->withCount('offerings')->ordered()->get(),
            'editing' => $this->editingId ? $this->query()->find($this->editingId) : null,
        ]);
    }
}
