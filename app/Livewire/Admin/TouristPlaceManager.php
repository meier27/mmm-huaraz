<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\TouristPlaceForm;
use App\Models\TouristPlace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Turismo')]
class TouristPlaceManager extends ContentManager
{
    use WithFileUploads;

    public TouristPlaceForm $form;

    protected function modelClass(): string
    {
        return TouristPlace::class;
    }

    /** @param TouristPlace $record */
    protected function afterSave(Model $record): void
    {
        if ($this->form->image) {
            $record->replaceImage($this->form->image);
        }
    }

    public function removeImage(int $id): void
    {
        $this->findRecord($id)->removeImage();
    }

    public function render(): View
    {
        return view('livewire.admin.tourist-place-manager', [
            'records' => TouristPlace::query()->ordered()->get(),
            'editing' => $this->editingId ? TouristPlace::find($this->editingId) : null,
        ]);
    }
}
