<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Player;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Jugadores: búsqueda y bloqueo (p. ej. por un apodo inapropiado). Un
 * jugador bloqueado no puede ingresar ni aparece en el ranking.
 */
#[Layout('components.layouts.admin')]
#[Title('Jugadores')]
class PlayerIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function boot(): void
    {
        $this->authorize('manage-content');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $player = Player::query()->findOrFail($id);
        $player->forceFill(['is_active' => ! $player->is_active])->save();
        AuditLog::record('updated', $player, ['is_active' => ! $player->is_active], ['is_active' => $player->is_active]);
    }

    public function render(): View
    {
        $search = str_replace(['%', '_'], '', trim($this->search));

        return view('livewire.admin.player-index', [
            'players' => Player::query()
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('nickname', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                ->withSum('progress as stars', 'stars')
                ->withCount(['progress as levels' => fn ($q) => $q->where('stars', '>', 0)])
                ->latest()
                ->paginate(25),
        ]);
    }
}
