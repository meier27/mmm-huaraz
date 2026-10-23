<?php

namespace App\Livewire\Admin;

use App\Enums\GameItemType;
use App\Livewire\Forms\GameItemForm;
use App\Models\GameItem;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Juegos')]
class GameItemManager extends ContentManager
{
    public GameItemForm $form;

    /** Pestaña: preguntas o palabras. */
    #[Url(as: 'tipo')]
    public string $tab = 'question';

    protected function modelClass(): string
    {
        return GameItem::class;
    }

    public function create(): void
    {
        parent::create();
        $this->form->type = GameItemType::tryFrom($this->tab)?->value ?? GameItemType::Question->value;
    }

    public function render(): View
    {
        $type = GameItemType::tryFrom($this->tab) ?? GameItemType::Question;

        return view('livewire.admin.game-item-manager', [
            'type' => $type,
            'records' => GameItem::query()->ofType($type)->ordered()->get(),
            'counts' => GameItem::query()->published()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
        ]);
    }
}
