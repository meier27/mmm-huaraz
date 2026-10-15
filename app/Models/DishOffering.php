<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        $query->where('is_active', true)
            ->whereDate('serve_date', '>=', today())
            ->whereHas('dish', fn (Builder $dish) => $dish->where('is_published', true));
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
