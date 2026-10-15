<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\BannerForm;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Banners de portada')]
class BannerManager extends ContentManager
{
    use WithFileUploads;

    public BannerForm $form;

    protected function modelClass(): string
    {
        return Banner::class;
    }

    /** @param Banner $record */
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
        return view('livewire.admin.banner-manager', [
            'records' => Banner::query()->ordered()->get(),
            'editing' => $this->editingId ? Banner::find($this->editingId) : null,
        ]);
    }
}
