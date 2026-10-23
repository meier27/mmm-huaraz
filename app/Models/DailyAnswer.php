<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Respuesta de un jugador al reto del día (una por día). */
class DailyAnswer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['day' => 'date', 'correct' => 'boolean'];
    }
}
