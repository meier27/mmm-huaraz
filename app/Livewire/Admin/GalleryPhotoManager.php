<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\GalleryPhotoForm;
use App\Models\GalleryPhoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Fotos de «Nuestra familia»')]
class GalleryPhotoManager extends ContentManager
{
    use WithFileUploads;

    public GalleryPhotoForm $form;

    protected function modelClass(): string
    {
        return GalleryPhoto::class;
    }

    public function save(): void
    {
        // Una foto nueva sin imagen no tendría nada que mostrar.
        if (! $this->editingId && ! $this->form->image) {
            $this->form->validate();
            $this->addError('form.image', 'Selecciona una foto.');

            return;
        }

        parent::save();
    }

    /** @param GalleryPhoto $record */
    protected function afterSave(Model $record): void
    {
        if ($this->form->image) {
            $record->replaceImage($this->form->image);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.gallery-photo-manager', [
            'records' => GalleryPhoto::query()->ordered()->get(),
            'onHome' => GalleryPhoto::query()->forHome()->pluck('id'),
            'editing' => $this->editingId ? GalleryPhoto::find($this->editingId) : null,
            'slots' => GalleryPhoto::HOME_SLOTS,
        ]);
    }
}
