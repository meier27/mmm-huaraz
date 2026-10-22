<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use App\Support\Markdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Evento del movimiento. Un evento simple solo tiene fecha, lugar y
 * descripción; uno grande (como la convención) activa módulos: programa,
 * sedes y donaciones.
 */
class ChurchEvent extends Model
{
    use Auditable, HasPublicImage;

    /**
     * Módulos que un evento puede activar.
     *
     * @var array<string, array{column: string, label: string, icon: string}>
     */
    public const MODULES = [
        'program' => ['column' => 'has_program', 'label' => 'Programa', 'icon' => 'list'],
        'venues' => ['column' => 'has_venues', 'label' => 'Sedes y ubicación', 'icon' => 'map-pin'],
        'donations' => ['column' => 'has_donations', 'label' => 'Donaciones', 'icon' => 'heart'],
    ];

    /** Slugs que no puede usar un evento (rutas antiguas o reservadas). */
    private const RESERVED_SLUGS = ['convencion'];

    protected string $imageColumn = 'image_path';

    /** @var array{int, int} Tamaño máximo al optimizar la imagen subida. */
    protected array $imageBounds = [2400, 1400];

    protected $fillable = [
        'title', 'slug', 'organization', 'theme', 'city', 'starts_on', 'ends_on', 'time_label', 'location', 'summary', 'body',
        'image_alt', 'hero_text', 'hero_button_label', 'hero_button_url', 'hero_show_title', 'hero_show_buttons', 'donation_reports_enabled',
        'external_url', 'is_published', 'is_featured',
        'has_program', 'has_venues', 'has_donations',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'hero_show_title' => 'boolean',
            'hero_show_buttons' => 'boolean',
            'donation_reports_enabled' => 'boolean',
            'has_program' => 'boolean',
            'has_venues' => 'boolean',
            'has_donations' => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    public function programDays(): HasMany
    {
        return $this->hasMany(ProgramDay::class)->orderBy('date');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** Próximos o en curso; los que no tienen fecha también, después de los fechados. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereDate('ends_on', '>=', today())
            ->orWhere(fn (Builder $q) => $q->whereNull('ends_on')->whereDate('starts_on', '>=', today()))
            ->orWhereNull('starts_on'))
            ->orderByRaw('starts_on is null')
            ->orderBy('starts_on');
    }

    public function scopePast(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereDate('ends_on', '<', today())->orWhere(fn (Builder $q) => $q->whereNull('ends_on')->whereDate('starts_on', '<', today())))
            ->orderByDesc('starts_on');
    }

    /**
     * Evento para la portada: el marcado como destacado (el más próximo si hay
     * varios) o, si no hay ninguno, el próximo evento publicado.
     */
    public static function featured(): ?self
    {
        return static::query()->published()->where('is_featured', true)->upcoming()->first()
            ?? static::query()->published()->where('is_featured', true)->orderByDesc('starts_on')->first()
            ?? static::query()->published()->upcoming()->first();
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'evento';
        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-evento';
        }
        $slug = $base;

        for ($i = 2; static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }

    // ------------------------------------------------------------------
    // Módulos
    // ------------------------------------------------------------------

    public function hasModule(string $module): bool
    {
        return (bool) $this->getAttribute(self::MODULES[$module]['column']);
    }

    public function hasAnyModule(): bool
    {
        return collect(self::MODULES)->keys()->contains(fn ($m) => $this->hasModule($m));
    }

    /** @return list<string> */
    public function enabledModules(): array
    {
        return collect(self::MODULES)->keys()->filter(fn ($m) => $this->hasModule($m))->values()->all();
    }

    // ------------------------------------------------------------------
    // Presentación
    // ------------------------------------------------------------------

    /** Si tiene fecha; sin ella no se muestra ningún texto de fecha. */
    public function hasDates(): bool
    {
        return $this->starts_on !== null;
    }

    public function endDate(): ?Carbon
    {
        return $this->hasDates() ? ($this->ends_on ?? $this->starts_on) : null;
    }

    public function isPast(): bool
    {
        return $this->hasDates() && $this->endDate()->lt(today());
    }

    public function isOver(?Carbon $today = null): bool
    {
        return $this->hasDates() && ($today ?? now())->startOfDay()->gt($this->endDate());
    }

    public function dateLabel(): string
    {
        if (! $this->hasDates()) {
            return '';
        }

        $start = $this->starts_on;
        $end = $this->ends_on;

        if (! $end || $end->equalTo($start)) {
            return ucfirst($start->translatedFormat('l j \d\e F \d\e Y'));
        }

        return $start->isSameMonth($end)
            ? $start->day.' al '.$end->translatedFormat('j \d\e F \d\e Y')
            : $start->translatedFormat('j \d\e F').' al '.$end->translatedFormat('j \d\e F \d\e Y');
    }

    /** Estado relativo a hoy, calculado en el servidor (sin JavaScript). */
    public function countdownLabel(?Carbon $today = null): string
    {
        if (! $this->hasDates()) {
            return '';
        }

        $today = ($today ?? now())->startOfDay();

        if ($today->lt($this->starts_on)) {
            $days = (int) $today->diffInDays($this->starts_on);

            return $days === 1 ? 'Falta 1 día' : "Faltan {$days} días";
        }

        return $today->lte($this->endDate()) ? 'En curso' : 'Finalizado';
    }

    public function renderedBody(): HtmlString
    {
        return Markdown::render($this->body);
    }
}
