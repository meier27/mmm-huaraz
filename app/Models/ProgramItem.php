<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramItem extends Model
{
    use Auditable;

    protected $fillable = [
        'program_day_id', 'venue_id', 'starts_at', 'ends_at', 'title', 'speaker', 'description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(ProgramDay::class, 'program_day_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function timeLabel(): string
    {
        $start = substr($this->starts_at, 0, 5);

        return $this->ends_at ? $start.' – '.substr($this->ends_at, 0, 5) : $start;
    }
}
