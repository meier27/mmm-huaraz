<?php

namespace App\Models\Concerns;

use App\Models\ChurchEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contenido de un módulo de evento (programa, sedes, platos, hospedaje...).
 * church_event_id no es asignable en masa: lo fija el servidor.
 *
 * @mixin Model
 */
trait BelongsToEvent
{
    public function event(): BelongsTo
    {
        return $this->belongsTo(ChurchEvent::class, 'church_event_id');
    }

    public function scopeForEvent(Builder $query, ChurchEvent|int $event): void
    {
        $query->where($this->qualifyColumn('church_event_id'), $event instanceof ChurchEvent ? $event->getKey() : $event);
    }
}
