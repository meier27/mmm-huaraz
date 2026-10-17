<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ScopedToEvent;
use App\Livewire\Forms\ProgramItemForm;
use App\Models\ProgramDay;
use App\Models\ProgramItem;
use App\Models\Venue;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Programa')]
class ProgramItemManager extends ContentManager
{
    use ScopedToEvent;

    public ProgramItemForm $form;

    protected function module(): string
    {
        return 'program';
    }

    protected function modelClass(): string
    {
        return ProgramItem::class;
    }

    public function render(): View
    {
        return view('livewire.admin.program-item-manager', [
            'days' => ProgramDay::query()->forEvent($this->eventId)->with('items.venue')->orderBy('date')->get(),
            'venues' => Venue::query()->forEvent($this->eventId)->ordered()->get(['id', 'name']),
        ]);
    }
}
