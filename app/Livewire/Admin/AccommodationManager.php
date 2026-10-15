<?php

namespace App\Livewire\Admin;

use App\Enums\AccommodationType;
use App\Livewire\Forms\AccommodationForm;
use App\Models\Accommodation;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Hospedaje')]
class AccommodationManager extends ContentManager
{
    public AccommodationForm $form;

    protected function modelClass(): string
    {
        return Accommodation::class;
    }

    public function render(): View
    {
        return view('livewire.admin.accommodation-manager', [
            'records' => Accommodation::query()->ordered()->get(),
            'types' => AccommodationType::cases(),
        ]);
    }
}
