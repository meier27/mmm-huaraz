<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

/**
 * Versículo destacado («versículo del día»). Se muestra el primero publicado
 * según el orden; el equipo lo rota desde el panel.
 */
class Verse extends Model
{
    use Auditable, Publishable;

    protected $fillable = ['reference', 'text', 'version', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
