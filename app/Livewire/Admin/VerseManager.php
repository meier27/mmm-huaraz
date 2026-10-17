<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\VerseForm;
use App\Models\Verse;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Versículos destacados')]
class VerseManager extends ContentManager
{
    public VerseForm $form;

    protected function modelClass(): string
    {
        return Verse::class;
    }

    public function render(): View
    {
        return view('livewire.admin.verse-manager', [
            'records' => Verse::query()->ordered()->get(),
        ]);
    }
}
