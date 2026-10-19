<?php

namespace App\Livewire\Admin;

use App\Models\Reservation;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Verificador para el punto de entrega: el personal escribe el código que
 * muestra el cliente y ve al instante si es válido y en qué estado está.
 */
#[Layout('components.layouts.admin')]
#[Title('Verificar código')]
class ReservationVerifier extends Component
{
    public string $code = '';

    #[Locked]
    public ?int $foundId = null;

    #[Locked]
    public bool $searched = false;

    public function boot(): void
    {
        $this->authorize('viewAny', Reservation::class);
    }

    public function verify(): void
    {
        $this->validate(['code' => ['required', 'string', 'max:30']], [], ['code' => 'código']);

        // Se aceptan minúsculas, espacios y guiones al dictarlo o escribirlo.
        $this->code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->code));
        $this->foundId = Reservation::query()->where('code', $this->code)->value('id');
        $this->searched = true;
    }

    public function clear(): void
    {
        $this->reset('code', 'foundId', 'searched');
    }

    public function render(): View
    {
        return view('livewire.admin.reservation-verifier', [
            'reservation' => $this->foundId
                ? Reservation::query()->with(['items.offering.dish', 'event:id,title'])->find($this->foundId)
                : null,
        ]);
    }
}
