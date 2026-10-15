<?php

namespace App\Livewire\Admin;

use App\Enums\DonationMethodType;
use App\Livewire\Forms\DonationMethodForm;
use App\Models\DonationMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Métodos de donación')]
class DonationMethodManager extends ContentManager
{
    use WithFileUploads;

    public DonationMethodForm $form;

    protected function modelClass(): string
    {
        return DonationMethod::class;
    }

    /** @param DonationMethod $record */
    protected function afterSave(Model $record): void
    {
        if ($this->form->qr_image) {
            $record->replaceImage($this->form->qr_image);
        }
    }

    public function removeImage(int $id): void
    {
        $this->findRecord($id)->removeImage();
    }

    public function render(): View
    {
        return view('livewire.admin.donation-method-manager', [
            'records' => DonationMethod::query()->ordered()->get(),
            'types' => DonationMethodType::cases(),
            'editing' => $this->editingId ? DonationMethod::find($this->editingId) : null,
        ]);
    }
}
