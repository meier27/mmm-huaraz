<?php

namespace App\Livewire;

use App\Exceptions\ReservationException;
use App\Livewire\Concerns\ResolvesReservationToken;
use App\Models\DishOffering;
use App\Services\ReservationService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.public', ['noindex' => true])]
#[Title('Cambiar mi pedido')]
class ReservationChangeForm extends Component
{
    use ResolvesReservationToken;

    #[Locked]
    public string $token;

    /** @var array<int|string, int|string> */
    public array $quantities = [];

    public function mount(string $token): void
    {
        $this->token = $token;
        $reservation = $this->reservationFromToken($token)->load('items.offering');

        if (! $reservation->customerCanChange()) {
            session()->flash('reservation-notice', 'El plazo para cambiar este pedido ya terminó.');
            $this->redirect(route('reservations.show', $token));

            return;
        }

        $current = $reservation->items->pluck('quantity', 'dish_offering_id')->all();
        $this->quantities = DishOffering::query()->forEvent($reservation->church_event_id)->bookable()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $current[$id] ?? 0])
            ->all();
    }

    public function save(ReservationService $service): void
    {
        $max = config('reservations.max_quantity_per_item');
        $this->validate(['quantities' => ['required', 'array'], 'quantities.*' => ['integer', 'min:0', "max:$max"]], [], ['quantities.*' => 'cantidad']);

        try {
            $service->change($this->reservationFromToken($this->token), $this->quantities);
        } catch (ReservationException $e) {
            throw ValidationException::withMessages(['quantities' => $e->getMessage()]);
        }

        session()->flash('reservation-notice', 'Tu pedido se actualizó. Tu código y QR siguen siendo los mismos.');
        $this->redirect(route('reservations.show', $this->token));
    }

    public function render(): View
    {
        $reservation = $this->reservationFromToken($this->token)->load('items.offering.dish');
        $offerings = DishOffering::query()->forEvent($reservation->church_event_id)->bookable()->with('dish')->orderBy('serve_date')->orderBy('slot_start')->get();

        return view('livewire.reservation-change-form', [
            'reservation' => $reservation,
            'groups' => $offerings->groupBy(fn ($o) => $o->serve_date->toDateString()),
            'prices' => $offerings->mapWithKeys(fn ($o) => [$o->id => (float) $o->price]),
            'held' => $reservation->items->pluck('quantity', 'dish_offering_id')->all(),
            'maxPerItem' => config('reservations.max_quantity_per_item'),
        ])->layoutData(['event' => $reservation->event]);
    }
}
