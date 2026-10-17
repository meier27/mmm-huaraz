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

    /** Al cambiar el tipo, sugiere el nombre visible si estaba vacío o era el de otro tipo. */
    public function updatedFormType(string $type): void
    {
        $labels = collect(DonationMethodType::cases())->map->label();

        if (blank($this->form->label) || $labels->contains($this->form->label)) {
            $this->form->label = $type === DonationMethodType::BankTransfer->value ? '' : (DonationMethodType::tryFrom($type)?->label() ?? '');
        }
    }

    /** @param DonationMethod $record */
    protected function afterSave(Model $record): void
    {
        // Las transferencias bancarias no llevan QR: si se cambió el tipo, se retira.
        if (! in_array('qr', DonationMethodForm::fieldsFor($this->form->type), true)) {
            if ($record->imageUrl()) {
                $record->removeImage();
            }
        } elseif ($this->form->qr_image) {
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
            'fields' => DonationMethodForm::fieldsFor($this->form->type),
        ]);
    }
}
