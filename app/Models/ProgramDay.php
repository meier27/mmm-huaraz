<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramDay extends Model
{
    use Auditable;

    protected $fillable = ['date', 'title'];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProgramItem::class)->orderBy('starts_at')->orderBy('id');
    }

    public function label(): string
    {
        return ucfirst($this->date->translatedFormat('l j \d\e F'));
    }
}
