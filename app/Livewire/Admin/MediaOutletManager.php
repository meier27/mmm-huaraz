<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ManagesRecordImage;
use App\Livewire\Forms\MediaOutletForm;
use App\Models\MediaOutlet;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Medios')]
class MediaOutletManager extends ContentManager
{
    use ManagesRecordImage;

    public MediaOutletForm $form;

    protected function modelClass(): string
    {
        return MediaOutlet::class;
    }

    public function render(): View
    {
        return view('livewire.admin.media-outlet-manager', [
            'records' => MediaOutlet::query()->ordered()->get(),
            'editing' => $this->editingId ? MediaOutlet::find($this->editingId) : null,
        ]);
    }
}
