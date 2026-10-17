<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DonationStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ChurchEvent;
use App\Models\DishOffering;
use App\Models\Donation;
use App\Models\DonationMethod;
use App\Models\Faq;
use App\Models\Leader;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Teaching;
use App\Models\Verse;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tablero del panel. Solo cifras reales y solo las que el rol puede ver.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // El gráfico y las ofertas se refieren al evento destacado (si tiene comidas).
        $data = ['tiles' => [], 'event' => ChurchEvent::featured()];

        if (Gate::allows('manage-reservations')) {
            $data += $this->reservations($data['event']?->has_food ? $data['event'] : null);
        }

        if (Gate::allows('manage-donations')) {
            $data += $this->donations();
        }

        if (Gate::allows('manage-content')) {
            $data['content'] = $this->content();
            $data['contentTiles'] = array_map(fn ($c) => ['label' => $c['label'], 'hint' => 'Publicado en la web', 'value' => number_format($c['count']), 'icon' => $c['icon'], 'route' => $c['route']], array_slice($data['content'], 0, 4));
        }

        // Tarjetas de cabecera (cuatro). Con reservas y aportes: tres de
        // reservas (sin porciones, que ya muestra el gráfico) y los aportes pendientes.
        $reservationTiles = $data['reservationTiles'] ?? [];
        if ($reservationTiles && isset($data['donationTiles'])) {
            $reservationTiles = [$reservationTiles[0], $reservationTiles[2], $reservationTiles[3]];
        }
        $data['tiles'] = array_slice(array_merge($reservationTiles, $data['donationTiles'] ?? [], $data['contentTiles'] ?? []), 0, 4);

        return view('admin.dashboard', $data);
    }

    private function reservations(?ChurchEvent $event): array
    {
        $active = Reservation::query()->where('status', '!=', ReservationStatus::Cancelled);

        if (! $event) {
            return ['perDay' => null, 'offerings' => collect(), 'reservationTiles' => $this->reservationTiles($active, null)];
        }

        $portions = ReservationItem::query()
            ->whereHas('reservation', fn ($q) => $q->where('status', '!=', ReservationStatus::Cancelled)->forEvent($event))
            ->join('dish_offerings', 'dish_offerings.id', '=', 'reservation_items.dish_offering_id')
            ->selectRaw('dish_offerings.serve_date as day, SUM(reservation_items.quantity) as portions')
            ->groupBy('dish_offerings.serve_date')
            ->pluck('portions', 'day')
            ->mapWithKeys(fn ($v, $day) => [substr((string) $day, 0, 10) => (int) $v]);

        // Un punto por cada día del evento, aunque aún no tenga reservas.
        $perDay = $event->hasDates() ? collect(CarbonPeriod::create($event->starts_on, $event->endDate()))
            ->map(fn ($day) => ['label' => ucfirst($day->translatedFormat('D j')), 'date' => $day->translatedFormat('l j \d\e F'), 'value' => $portions[$day->toDateString()] ?? 0])
            ->values() : null;

        return [
            'perDay' => $perDay,
            'offerings' => DishOffering::query()
                ->with('dish:id,name')
                ->forEvent($event)
                ->where('is_active', true)
                ->whereDate('serve_date', '>=', today())
                ->orderBy('serve_date')->orderBy('slot_start')
                ->limit(8)
                ->get(),
            'reservationTiles' => $this->reservationTiles($active, $portions->sum()),
        ];
    }

    /** Totales de todas las reservas activas (de cualquier evento). */
    private function reservationTiles(Builder $active, ?int $eventPortions): array
    {
        $collected = (clone $active)->sum('amount_paid');
        $pending = (clone $active)->selectRaw('COALESCE(SUM(total - amount_paid), 0) as due')->value('due');

        return [
            ['label' => 'Reservas activas', 'value' => number_format((clone $active)->count()), 'icon' => 'qr', 'route' => 'admin.reservations'],
            ['label' => 'Porciones del evento destacado', 'value' => number_format($eventPortions ?? 0), 'icon' => 'utensils', 'route' => 'admin.reservations'],
            ['label' => 'Cobrado en reservas', 'value' => 'S/ '.number_format((float) $collected, 2), 'icon' => 'coins', 'route' => 'admin.reservations', 'accent' => true],
            ['label' => 'Saldo por cobrar', 'value' => 'S/ '.number_format((float) $pending, 2), 'icon' => 'clock', 'route' => 'admin.reservations'],
        ];
    }

    private function donations(): array
    {
        $pending = Donation::query()->status(DonationStatus::Pending);

        return [
            'pendingDonations' => (clone $pending)->latest()->limit(5)->get(),
            'pendingDonationsCount' => (clone $pending)->count(),
            'donationTiles' => [
                ['label' => 'Aportes por verificar', 'value' => number_format((clone $pending)->count()), 'icon' => 'coins', 'route' => 'admin.donations'],
                ['label' => 'Aportes verificados', 'value' => 'S/ '.number_format((float) Donation::query()->status(DonationStatus::Verified)->sum('verified_amount'), 2), 'icon' => 'heart', 'route' => 'admin.donations', 'accent' => true],
            ],
        ];
    }

    /** @return list<array{route: string, label: string, count: int, icon: string}> */
    private function content(): array
    {
        return [
            ['route' => 'admin.teachings', 'label' => 'Enseñanzas', 'count' => Teaching::published()->count(), 'icon' => 'book'],
            ['route' => 'admin.events', 'label' => 'Próximos eventos', 'count' => ChurchEvent::published()->upcoming()->count(), 'icon' => 'calendar'],
            ['route' => 'admin.verses', 'label' => 'Versículos', 'count' => Verse::published()->count(), 'icon' => 'quote'],
            ['route' => 'admin.leaders', 'label' => 'Líderes', 'count' => Leader::published()->count(), 'icon' => 'users'],
            ['route' => 'admin.events', 'label' => 'Eventos con módulos', 'count' => ChurchEvent::published()->get()->filter->hasAnyModule()->count(), 'icon' => 'star'],
            ['route' => 'admin.banners', 'label' => 'Banners', 'count' => Banner::published()->count(), 'icon' => 'image'],
            ['route' => 'admin.donation-methods', 'label' => 'Métodos de donación', 'count' => DonationMethod::published()->count(), 'icon' => 'heart'],
            ['route' => 'admin.faqs', 'label' => 'Preguntas frecuentes', 'count' => Faq::published()->count(), 'icon' => 'help'],
        ];
    }
}
