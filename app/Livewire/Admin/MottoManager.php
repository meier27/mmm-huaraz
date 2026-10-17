<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ManagesRecordImage;
use App\Livewire\Forms\MottoForm;
use App\Models\Motto;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Lemas')]
class MottoManager extends ContentManager
{
    use ManagesRecordImage;

    public MottoForm $form;

    protected function modelClass(): string
    {
        return Motto::class;
    }

    public function create(): void
    {
        parent::create();
        $this->form->year = (int) now()->year;
    }

    public function render(): View
    {
        return view('livewire.admin.motto-manager', [
            'records' => Motto::query()->orderByDesc('year')->get(),
            'editing' => $this->editingId ? Motto::find($this->editingId) : null,
            'currentId' => Motto::current()?->id,
        ]);
    }
}
