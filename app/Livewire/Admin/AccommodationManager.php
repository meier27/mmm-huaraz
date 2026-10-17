<?php

namespace App\Livewire\Admin;

use App\Enums\AccommodationType;
use App\Livewire\Admin\Concerns\ScopedToEvent;
use App\Livewire\Forms\AccommodationForm;
use App\Models\Accommodation;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Hospedaje')]
class AccommodationManager extends ContentManager
{
    use ScopedToEvent;

    public AccommodationForm $form;

    protected function module(): string
    {
        return 'accommodations';
    }

    protected function modelClass(): string
    {
        return Accommodation::class;
    }

    public function render(): View
    {
        return view('livewire.admin.accommodation-manager', [
            'records' => $this->query()->ordered()->get(),
            'types' => AccommodationType::cases(),
        ]);
    }
}
