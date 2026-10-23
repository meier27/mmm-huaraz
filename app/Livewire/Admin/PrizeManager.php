<?php

namespace App\Livewire\Admin;

use App\Enums\ClaimStatus;
use App\Livewire\Admin\Concerns\ManagesRecordImage;
use App\Livewire\Forms\PrizeForm;
use App\Models\Prize;
use Illuminate\View\View;
use Livewire\Attributes\Title;

#[Title('Premios de los juegos')]
class PrizeManager extends ContentManager
{
    use ManagesRecordImage;

    public PrizeForm $form;

    protected function modelClass(): string
    {
        return Prize::class;
    }

    /** Un premio ya canjeado no se borra (se pierde la constancia): se despublica. */
    public function delete(int $id): void
    {
        if ($this->findRecord($id)->claims()->exists()) {
            session()->flash('status', 'Este premio ya tiene canjes: no se puede eliminar. Despublícalo para que no se pueda reclamar más.');

            return;
        }

        parent::delete($id);
    }

    public function render(): View
    {
        return view('livewire.admin.prize-manager', [
            'records' => Prize::query()->ordered()
                ->withCount(['claims' => fn ($q) => $q->where('status', '!=', ClaimStatus::Cancelled->value)])->get(),
            'editing' => $this->editingId ? Prize::find($this->editingId) : null,
        ]);
    }
}
