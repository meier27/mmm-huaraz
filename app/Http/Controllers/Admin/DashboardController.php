<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ChurchEvent;
use App\Models\Donation;
use App\Models\DonationMethod;
use App\Models\Faq;
use App\Models\Leader;
use App\Models\Teaching;
use App\Models\Verse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tablero del panel. Solo cifras reales y solo las que el rol puede ver.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // El evento destacado se menciona en el saludo (con su cuenta atrás).
        $data = ['tiles' => [], 'event' => ChurchEvent::featured()];

        if (Gate::allows('manage-donations')) {
            $data += $this->donations();
        }

        if (Gate::allows('manage-content')) {
            $data['content'] = $this->content();
            $data['contentTiles'] = array_map(fn ($c) => ['label' => $c['label'], 'hint' => 'Publicado en la web', 'value' => number_format($c['count']), 'icon' => $c['icon'], 'route' => $c['route']], array_slice($data['content'], 0, 4));
        }

        // Tarjetas de cabecera (cuatro): aportes primero, luego contenido publicado.
        $data['tiles'] = array_slice(array_merge($data['donationTiles'] ?? [], $data['contentTiles'] ?? []), 0, 4);

        return view('admin.dashboard', $data);
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
