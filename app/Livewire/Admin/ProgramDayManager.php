<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ScopedToEvent;
use App\Livewire\Forms\ProgramDayForm;
use App\Models\ProgramDay;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Días del programa')]
class ProgramDayManager extends ContentManager
{
    use ScopedToEvent;

    public ProgramDayForm $form;

    protected function module(): string
    {
        return 'program';
    }

    protected function modelClass(): string
    {
        return ProgramDay::class;
    }

    public function render(): View
    {
        return view('livewire.admin.program-day-manager', [
            'records' => $this->query()->withCount('items')->orderBy('date')->get(),
        ]);
    }
}
