<?php

namespace App\Livewire\Admin;

use App\Enums\VenueKind;
use App\Livewire\Forms\VenueForm;
use App\Models\Venue;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Sedes y ubicaciones')]
class VenueManager extends ContentManager
{
    public VenueForm $form;

    protected function modelClass(): string
    {
        return Venue::class;
    }

    public function render(): View
    {
        return view('livewire.admin.venue-manager', [
            'records' => Venue::query()->ordered()->get(),
            'kinds' => VenueKind::cases(),
        ]);
    }
}
