<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\ChurchEvent;
use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Gestor (ContentManager) de un módulo de evento: lista, edita y crea solo
 * registros de ese evento. El id del evento está bloqueado: el navegador no
 * puede cambiarlo para tocar contenido de otro evento.
 */
trait ScopedToEvent
{
    #[Locked]
    public int $eventId;

    /** Módulo que el evento debe tener activo (clave de ChurchEvent::MODULES). */
    abstract protected function module(): string;

    public function mount(ChurchEvent $event): void
    {
        abort_unless($event->hasModule($this->module()), 404);
        $this->eventId = $event->id;
    }

    #[Computed]
    public function event(): ChurchEvent
    {
        return ChurchEvent::query()->findOrFail($this->eventId);
    }

    protected function query(): Builder
    {
        return $this->modelClass()::query()->forEvent($this->eventId);
    }

    /** Los registros nuevos quedan en el evento de la página. */
    protected function beforeSave(Model $record): void
    {
        if (! $record->exists && in_array(BelongsToEvent::class, class_uses_recursive($record), true)) {
            $record->setAttribute('church_event_id', $this->eventId);
        }
    }
}
