<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\ProgramItemForm;
use App\Models\ProgramDay;
use App\Models\ProgramItem;
use App\Models\Venue;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Programa')]
class ProgramItemManager extends ContentManager
{
    public ProgramItemForm $form;

    protected function modelClass(): string
    {
        return ProgramItem::class;
    }

    public function render(): View
    {
        return view('livewire.admin.program-item-manager', [
            'days' => ProgramDay::query()->with('items.venue')->orderBy('date')->get(),
            'venues' => Venue::query()->ordered()->get(['id', 'name']),
        ]);
    }
}
