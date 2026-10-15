<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\ProgramDayForm;
use App\Models\ProgramDay;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Días del programa')]
class ProgramDayManager extends ContentManager
{
    public ProgramDayForm $form;

    protected function modelClass(): string
    {
        return ProgramDay::class;
    }

    public function render(): View
    {
        return view('livewire.admin.program-day-manager', [
            'records' => ProgramDay::query()->withCount('items')->orderBy('date')->get(),
        ]);
    }
}
