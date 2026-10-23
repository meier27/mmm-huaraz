<?php

namespace App\Livewire\Admin;

use App\Enums\ClaimStatus;
use App\Models\PrizeClaim;
use App\Models\SiteSetting;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Canjes de premios: se busca el código que muestra el jugador y se marca
 * como entregado (o se anula, y la unidad vuelve a estar disponible).
 */
#[Layout('components.layouts.admin')]
#[Title('Canjes de premios')]
class PrizeClaimIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'pending')]
    public string $status = 'pending';

    public function boot(): void
    {
        $this->authorize('manage-content');
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    /** id del canje => el equipo verificó en el celular del jugador que sigue las páginas. */
    public array $verified = [];

    public function deliver(int $id): void
    {
        $this->resetErrorBag();
        $claim = PrizeClaim::query()->where('status', ClaimStatus::Pending)->findOrFail($id);
        $mustFollow = SiteSetting::current()->followLinks() !== [];

        if ($mustFollow && empty($this->verified[$id])) {
            $this->addError("verified.{$id}", 'Antes de entregar, verifica en su celular que sigue las páginas del Movimiento.');

            return;
        }

        $claim->forceFill([
            'status' => ClaimStatus::Delivered,
            'delivered_at' => now(),
            'delivered_by' => auth()->id(),
            'follow_verified_at' => $mustFollow ? now() : null,
        ])->save();
        session()->flash('status', "Entregado: {$claim->prize->title} a {$claim->player_nickname} ({$claim->code}).");
    }

    public function cancel(int $id): void
    {
        $claim = PrizeClaim::query()->where('status', ClaimStatus::Pending)->findOrFail($id);
        $claim->forceFill(['status' => ClaimStatus::Cancelled])->save();
        session()->flash('status', "Canje {$claim->code} anulado: la unidad vuelve a estar disponible.");
    }

    public function render(): View
    {
        $search = trim($this->search);

        return view('livewire.admin.prize-claim-index', [
            'followLinks' => SiteSetting::current()->followLinks(),
            'claims' => PrizeClaim::query()->with(['prize', 'player:id,nickname,name', 'deliveredBy:id,name'])
                ->when(ClaimStatus::tryFrom($this->status), fn ($q, $status) => $q->where('status', $status))
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('code', 'like', '%'.strtoupper(str_replace(['%', '_'], '', $search)).'%')
                    ->orWhere('player_nickname', 'like', '%'.str_replace(['%', '_'], '', $search).'%')))
                ->latest()
                ->paginate(20),
        ]);
    }
}
