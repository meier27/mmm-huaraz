<?php

namespace App\Livewire\Admin;

use App\Enums\VenueKind;
use App\Livewire\Admin\Concerns\ManagesRecordImage;
use App\Livewire\Admin\Concerns\ScopedToEvent;
use App\Livewire\Forms\VenueForm;
use App\Models\Venue;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Sedes y ubicaciones')]
class VenueManager extends ContentManager
{
    use ManagesRecordImage, ScopedToEvent;

    public VenueForm $form;

    protected function module(): string
    {
        return 'venues';
    }

    protected function modelClass(): string
    {
        return Venue::class;
    }

    public function render(): View
    {
        return view('livewire.admin.venue-manager', [
            'records' => $this->query()->ordered()->get(),
            'kinds' => VenueKind::cases(),
            'editing' => $this->editingId ? $this->query()->find($this->editingId) : null,
        ]);
    }
}
