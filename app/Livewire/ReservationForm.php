<?php

namespace App\Livewire;

use App\Models\ChurchEvent;
use App\Models\DishOffering;
use App\Services\ReservationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ReservationForm extends Component
{
    /** @var array<int|string, int|string> id de oferta => porciones */
    public array $quantities = [];

    public string $customer_name = '';

    public string $customer_phone = '';

    public string $notes = '';

    #[Locked]
    public string $idempotencyKey = '';

    /** Evento cuya actividad de comidas se reserva (no modificable desde el navegador). */
    #[Locked]
    public ChurchEvent $event;

    public function mount(ChurchEvent $event): void
    {
        abort_unless($event->is_published && $event->has_food, 404);
        $this->event = $event;

        $this->idempotencyKey = (string) Str::uuid();
        $this->quantities = $this->offerings()->mapWithKeys(fn ($offering) => [$offering->id => 0])->all();
    }

    public function submit(ReservationService $reservations): void
    {
        if (! $this->event->acceptsReservations()) {
            throw ValidationException::withMessages(['quantities' => 'Las reservas no están abiertas en este momento.']);
        }

        $key = 'reservation-create:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, config('reservations.attempts_per_hour'))) {
            throw ValidationException::withMessages(['quantities' => 'Has realizado demasiados intentos. Inténtalo de nuevo más tarde.']);
        }
        RateLimiter::hit($key, 3600);

        $max = config('reservations.max_quantity_per_item');
        $data = $this->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['integer', 'min:0', "max:$max"],
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s()-]{6,20}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'quantities.*' => 'cantidad',
            'customer_name' => 'nombre',
            'customer_phone' => 'celular',
            'notes' => 'observaciones',
        ]);

        $reservation = $reservations->create(
            $data['quantities'],
            trim($data['customer_name']),
            trim($data['customer_phone']),
            $data['notes'] ?: null,
            $this->idempotencyKey,
            $this->event,
        );

        $this->redirect($reservation->publicUrl());
    }

    public function render(): View
    {
        $offerings = $this->offerings();

        return view('livewire.reservation-form', [
            'event' => $this->event,
            'groups' => $offerings->groupBy(fn ($o) => $o->serve_date->toDateString()),
            'prices' => $offerings->mapWithKeys(fn ($o) => [$o->id => (float) $o->price]),
            'maxPerItem' => config('reservations.max_quantity_per_item'),
        ])->title($this->event->reservationsTitle().' · '.$this->event->title)
            ->layoutData(['event' => $this->event]);
    }

    /**
     * @return Collection<int, DishOffering>
     */
    private function offerings(): Collection
    {
        return DishOffering::query()
            ->forEvent($this->event)
            ->bookable()
            ->with('dish')
            ->orderBy('serve_date')
            ->orderBy('slot_start')
            ->get()
            ->sortBy(fn ($o) => [$o->serve_date->toDateString(), $o->slot_start, $o->dish->sort_order])
            ->values();
    }
}
