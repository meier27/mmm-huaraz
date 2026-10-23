<?php

namespace App\Models;

use App\Enums\GameItemType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pregunta (trivia) o palabra (crucigrama y sopa de letras) de los juegos bíblicos.
 */
class GameItem extends Model
{
    use Auditable, Publishable;

    protected $fillable = ['type', 'prompt', 'answer', 'options', 'reference', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => GameItemType::class,
            'options' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOfType(Builder $query, GameItemType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * La palabra tal como va en la cuadrícula: mayúsculas, sin tildes ni
     * espacios, con la Ñ («Jordán» → «JORDAN», «Caná» → «CANA»).
     */
    public static function gridWord(string $text): string
    {
        $text = strtr(mb_strtoupper(trim($text)), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);

        return preg_replace('/[^A-ZÑ]/u', '', $text);
    }
}
