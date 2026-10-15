<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DishOffering extends Model
{
    use Auditable;

    // "reserved" no es asignable: solo lo modifica ReservationService dentro de transacciones.
    protected $fillable = ['dish_id', 'serve_date', 'slot_start', 'slot_end', 'price', 'stock', 'is_active'];

    protected function casts(): array
    {
        return [
            'serve_date' => 'date:Y-m-d',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'reserved' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Ofertas visibles para reservar: activas, de platos publicados y no pasadas.
     */
    public function scopeBookable(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            // Solo horarios que aún no han empezado.
            ->where(fn (Builder $q) => $q
                ->whereDate('serve_date', '>', $now->toDateString())
                ->orWhere(fn (Builder $q) => $q->whereDate('serve_date', $now->toDateString())->where('slot_start', '>', $now->format('H:i:s'))))
            ->whereHas('dish', fn (Builder $dish) => $dish->where('is_published', true));
    }

    /** Horas siempre como H:i:s para que las comparaciones sean coherentes en cualquier motor. */
    protected function slotStart(): Attribute
    {
        return Attribute::set(fn (?string $value) => $value ? Carbon::parse($value)->format('H:i:s') : $value);
    }

    protected function slotEnd(): Attribute
    {
        return Attribute::set(fn (?string $value) => $value ? Carbon::parse($value)->format('H:i:s') : null);
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->serve_date->toDateString().' '.$this->slot_start);
    }

    public function endsAt(): Carbon
    {
        return $this->slot_end
            ? Carbon::parse($this->serve_date->toDateString().' '.$this->slot_end)
            : $this->startsAt()->addHours(config('reservations.pickup_window_hours'));
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function available(): int
    {
        return max(0, $this->stock - $this->reserved);
    }

    public function slotLabel(): string
    {
        $start = substr($this->slot_start, 0, 5);

        return $this->slot_end ? $start.' – '.substr($this->slot_end, 0, 5) : $start;
    }

    public function dateLabel(): string
    {
        return ucfirst($this->serve_date->translatedFormat('l j \d\e F'));
    }
}
