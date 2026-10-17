<?php

namespace App\Livewire\Admin;

use App\Enums\AboutBlockType;
use App\Livewire\Admin\Concerns\ManagesRecordImage;
use App\Livewire\Forms\AboutBlockForm;
use App\Models\AboutBlock;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Página «Nosotros»')]
class AboutBlockManager extends ContentManager
{
    use ManagesRecordImage;

    public AboutBlockForm $form;

    protected function modelClass(): string
    {
        return AboutBlock::class;
    }

    public function render(): View
    {
        return view('livewire.admin.about-block-manager', [
            'records' => AboutBlock::query()->ordered()->get(),
            'editing' => $this->editingId ? AboutBlock::find($this->editingId) : null,
            'types' => collect(AboutBlockType::cases())->mapWithKeys(fn (AboutBlockType $t) => [$t->value => $t->label()])->all(),
        ]);
    }
}
