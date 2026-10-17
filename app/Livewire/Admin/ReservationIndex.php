<?php

namespace App\Livewire\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\ChurchEvent;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Reservas')]
class ReservationIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $payment = '';

    #[Url(except: '')]
    public string $delivery = '';

    #[Url(as: 'evento', except: '')]
    public string $event = '';

    public function boot(): void
    {
        $this->authorize('viewAny', Reservation::class);
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'payment', 'delivery', 'event'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $search = trim($this->search);
        $digits = preg_replace('/\D+/', '', $search);

        $reservations = Reservation::query()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('code', strtoupper($search))
                ->orWhere('customer_name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->when(strlen($digits) >= 3, fn (Builder $q) => $q->orWhere('customer_phone', 'like', '%'.$digits.'%'))))
            ->when(ReservationStatus::tryFrom($this->status), fn (Builder $q, $s) => $q->where('status', $s))
            ->when(PaymentStatus::tryFrom($this->payment), fn (Builder $q, $s) => $q->where('payment_status', $s))
            ->when(DeliveryStatus::tryFrom($this->delivery), fn (Builder $q, $s) => $q->where('delivery_status', $s))
            ->when(ctype_digit($this->event), fn (Builder $q) => $q->forEvent((int) $this->event))
            ->withSum('items as portions', 'quantity')
            ->latest('id')
            ->paginate(20);

        return view('livewire.admin.reservation-index', [
            'reservations' => $reservations,
            'statuses' => ReservationStatus::cases(),
            'payments' => PaymentStatus::cases(),
            'deliveries' => DeliveryStatus::cases(),
            'events' => ChurchEvent::query()->where('has_food', true)->orderByDesc('starts_on')->pluck('title', 'id'),
        ]);
    }
}
