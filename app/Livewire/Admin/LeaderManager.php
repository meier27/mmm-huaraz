<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ManagesRecordImage;
use App\Livewire\Forms\LeaderForm;
use App\Models\Leader;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Líderes')]
class LeaderManager extends ContentManager
{
    use ManagesRecordImage;

    public LeaderForm $form;

    protected function modelClass(): string
    {
        return Leader::class;
    }

    public function render(): View
    {
        return view('livewire.admin.leader-manager', [
            'records' => Leader::query()->ordered()->get(),
            'editing' => $this->editingId ? Leader::find($this->editingId) : null,
        ]);
    }
}
