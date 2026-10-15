<?php

namespace App\Models;

use App\Enums\AccommodationType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    use Auditable, Publishable;

    protected $fillable = [
        'type', 'name', 'address', 'phone', 'price_range', 'website_url', 'maps_url', 'notes', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccommodationType::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
