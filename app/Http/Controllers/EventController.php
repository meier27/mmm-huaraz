<?php

namespace App\Http\Controllers;

use App\Enums\VenueKind;
use App\Models\ChurchEvent;
use App\Models\DonationMethod;
use App\Models\ProgramDay;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Eventos y sus módulos (programa, sedes y donaciones). Solo contenido
 * publicado, y cada módulo solo si el evento lo tiene activo.
 */
class EventController extends Controller
{
    /** Secciones de las URL antiguas de la convención => nombre de ruta actual. */
    private const LEGACY_SECTIONS = [
        '' => 'events.show',
        'programa' => 'events.program',
        'sede' => 'events.venue',
        'donaciones' => 'events.donations',
    ];

    public function index(): View
    {
        $featured = ChurchEvent::featured();

        return view('events.index', [
            'featured' => $featured,
            'upcoming' => ChurchEvent::query()->published()->upcoming()->when($featured, fn ($q) => $q->whereKeyNot($featured->id))
                ->paginate(8, pageName: 'proximos')->withQueryString()->fragment('proximos'),
            'past' => ChurchEvent::query()->published()->past()->when($featured, fn ($q) => $q->whereKeyNot($featured->id))
                ->paginate(8, pageName: 'anteriores')->withQueryString()->fragment('anteriores'),
        ]);
    }

    public function show(ChurchEvent $event): View
    {
        $this->guard($event);

        if (! $event->hasAnyModule()) {
            return view('events.show', ['event' => $event]);
        }

        // Todos los días con actividades publicadas; se abre el próximo (o el primero).
        $programDays = $event->has_program ? ProgramDay::query()
            ->forEvent($event)
            ->whereHas('items', fn ($q) => $q->published())
            ->with(['items' => fn (HasMany $q) => $q->published()])
            ->orderBy('date')
            ->get() : collect();
        $nextDay = $programDays->first(fn (ProgramDay $day) => $day->date->gte(today())) ?? $programDays->first();

        return view('events.hub', [
            'event' => $event,
            'mainVenue' => $event->has_venues ? $event->venues()->published()->where('kind', VenueKind::Main)->ordered()->first() : null,
            'programDays' => $programDays,
            'nextDay' => $nextDay,
        ]);
    }

    public function program(ChurchEvent $event): View
    {
        $this->guard($event, 'program');

        $days = $event->programDays()
            ->with(['items' => fn (HasMany $query) => $query->published()->with('venue:id,name')])
            ->get();

        // Un día por página (?dia=AAAA-MM-DD). Por defecto: hoy, el próximo día o el primero.
        $requested = request()->string('dia')->toString();
        $index = $days->search(fn (ProgramDay $day) => $day->date->toDateString() === $requested);
        if ($index === false) {
            $index = $days->search(fn (ProgramDay $day) => $day->date->gte(today()));
            $index = $index === false ? 0 : $index;
        }

        return view('events.modules.program', [
            'event' => $event,
            'days' => $days,
            'day' => $days->get($index),
            'previousDay' => $index > 0 ? $days->get($index - 1) : null,
            'nextDay' => $days->get($index + 1),
        ]);
    }

    public function venue(ChurchEvent $event): View
    {
        $this->guard($event, 'venues');

        return view('events.modules.venue', [
            'event' => $event,
            'venues' => $event->venues()->published()->ordered()->get(),
        ]);
    }

    public function donations(ChurchEvent $event): View
    {
        $this->guard($event, 'donations');

        // Los métodos de donación son generales de la iglesia.
        return view('events.modules.donations', [
            'event' => $event,
            'methods' => DonationMethod::query()->published()->ordered()->get(),
        ]);
    }

    /** Secciones retiradas (comidas, hospedaje, turismo): se redirige a la página del evento. */
    public function retired(ChurchEvent $event): RedirectResponse
    {
        $this->guard($event);

        return redirect()->route('events.show', $event, 301);
    }

    /** /eventos/convencion/... y /programa, /sede... (antes de los eventos con módulos). */
    public function legacy(?string $section = null): RedirectResponse
    {
        $route = self::LEGACY_SECTIONS[$section ?? ''] ?? abort(404);
        $event = ChurchEvent::featured() ?? abort(404);

        return redirect()->route($route, $event, 301);
    }

    private function guard(ChurchEvent $event, ?string $module = null): void
    {
        abort_unless($event->is_published && ($module === null || $event->hasModule($module)), 404);
    }
}
