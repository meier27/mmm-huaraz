<?php

namespace App\Livewire\Admin;

use App\Enums\PrizeGoal;
use App\Models\Prize;
use App\Models\WeeklyWinner;
use App\Services\GameRewards;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Ranking semanal: el equipo revisa la semana terminada, elige el premio de
 * cada puesto y la cierra; los ganadores reciben su código de canje.
 */
#[Layout('components.layouts.admin')]
#[Title('Ranking semanal')]
class WeeklyRankingManager extends Component
{
    /** Lunes de la semana (AAAA-MM-DD). */
    #[Url(as: 'semana', except: '')]
    public string $week = '';

    /** Puesto => id del premio. */
    public array $prizes = [];

    public function boot(): void
    {
        $this->authorize('manage-content');
    }

    public function mount(): void
    {
        // Por defecto, la última semana terminada (la que toca cerrar).
        $this->week = $this->week ?: GameRewards::week()->subWeek()->toDateString();
    }

    public function close(GameRewards $rewards): void
    {
        $this->validate([
            'prizes' => ['array'],
            'prizes.*' => ['nullable', 'integer', 'exists:prizes,id'],
        ]);

        try {
            $winners = $rewards->closeWeek($this->monday(), array_map(fn ($id) => $id ? (int) $id : null, $this->prizes), auth()->id());
        } catch (HttpException $e) {
            $this->addError('prizes.0', $e->getMessage());

            return;
        }
        $this->prizes = [];

        session()->flash('status', $winners->isEmpty()
            ? 'Semana cerrada sin ganadores (nadie sumó puntos).'
            : 'Semana cerrada: '.$winners->count().' ganadores. Sus códigos aparecen en «Mi avance» y en Panel › Canjes.');
    }

    private function monday(): Carbon
    {
        return GameRewards::week(Carbon::parse($this->week ?: 'now'));
    }

    public function render(GameRewards $rewards): View
    {
        $week = $this->monday();
        $winners = WeeklyWinner::query()->with('claim.prize')->whereDate('week', $week->toDateString())->orderBy('position')->get();

        return view('livewire.admin.weekly-ranking-manager', [
            'monday' => $week,
            'sunday' => $week->copy()->endOfWeek(Carbon::SUNDAY),
            'isCurrent' => $week->isSameDay(GameRewards::week()),
            'ended' => $week->copy()->endOfWeek(Carbon::SUNDAY)->isPast(),
            'ranking' => $rewards->weeklyRanking($week, 10),
            'candidates' => $winners->isEmpty() ? $rewards->candidates($week) : collect(),
            'winners' => $winners,
            'weeklyPrizes' => Prize::query()->published()->where('goal_type', PrizeGoal::Weekly->value)->ordered()->get(),
            'weeks' => collect(range(0, 7))->map(fn ($i) => GameRewards::week()->subWeeks($i)),
        ]);
    }
}
