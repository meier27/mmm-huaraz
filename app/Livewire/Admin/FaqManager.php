<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\FaqForm;
use App\Models\Faq;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Preguntas frecuentes')]
class FaqManager extends ContentManager
{
    public FaqForm $form;

    protected function modelClass(): string
    {
        return Faq::class;
    }

    public function render(): View
    {
        return view('livewire.admin.faq-manager', [
            'records' => Faq::query()->ordered()->get(),
        ]);
    }
}
