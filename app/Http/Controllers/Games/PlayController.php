<?php

namespace App\Http\Controllers\Games;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\GameAttempt;
use App\Models\GameProgress;
use App\Models\Player;
use App\Models\Prize;
use App\Services\GameCatalog;
use App\Services\GameRewards;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Juegos por niveles. Sin cuenta se prueban los primeros niveles (no se
 * guardan); con cuenta, cada nivel superado desbloquea el siguiente.
 */
class PlayController extends Controller
{
    public function __construct(private readonly GameCatalog $games, private readonly GameRewards $rewards) {}

    public function index(): View
    {
        $player = $this->player();

        return view('games.index', [
            'games' => $this->games->catalog(),
            'stats' => $player?->stats(),
            'weeklyPoints' => $player ? $this->rewards->weeklyPointsOf($player) : null,
            'streak' => $player ? GameRewards::currentStreak($player) : null,
            'daily' => $this->rewards->dailyChallenge(),
            'dailyAnswer' => $player ? $this->rewards->dailyAnswerOf($player) : null,
            'weeklyTop' => $this->rewards->weeklyRanking(GameRewards::week(), 3),
            'prizes' => Prize::query()->published()->ordered()->withCount(['claims' => fn ($q) => $q->where('status', '!=', ClaimStatus::Cancelled->value)])->get(),
        ]);
    }

    /** Mapa de los 30 niveles de un juego. */
    public function levels(string $game): View
    {
        $info = $this->info($game);
        $player = $this->player();
        $progress = $player ? $player->progress()->where('game', $game)->pluck('stars', 'level') : collect();
        $unlocked = $player ? $player->unlockedLevel($game) : GameCatalog::GUEST_LEVELS;
        $engine = $this->games->engine($game);

        return view('games.levels', [
            'game' => $info,
            'levels' => collect(range(1, GameCatalog::LEVELS))->map(fn ($n) => [
                'number' => $n,
                'stars' => (int) ($progress[$n] ?? 0),
                'open' => $n <= $unlocked,
                'text' => $engine->describe($n),
            ]),
            'player' => $player,
            'stats' => $player?->stats()['games'][$game] ?? null,
            'others' => $this->games->catalog()->except($game),
        ]);
    }

    public function play(Request $request, string $game, int $level): View|RedirectResponse
    {
        $info = $this->info($game);
        abort_unless($info['ready'] && $level >= 1 && $level <= GameCatalog::LEVELS, 404);
        $player = $this->player();

        if (! $player && $level > GameCatalog::GUEST_LEVELS) {
            return redirect()->route('player.login')->with('status', 'Inicia sesión o crea tu cuenta para jugar desde el nivel '.(GameCatalog::GUEST_LEVELS + 1).' y guardar tu avance.');
        }
        if ($player && $level > $player->unlockedLevel($game)) {
            return redirect()->route('games.levels', $game)->with('status', 'Supera el nivel '.($level - 1).' para desbloquear el nivel '.$level.'.');
        }

        $round = $this->games->engine($game)->start($level);
        $attempt = GameAttempt::create([
            'player_id' => $player?->id,
            'session_id' => $player ? null : $request->session()->getId(),
            'game' => $game,
            'level' => $level,
            'secret' => $round['secret'],
            'state' => $round['state'],
        ]);
        $this->prune();

        return view('games.play', [
            'game' => $info,
            'level' => $level,
            'description' => $this->games->engine($game)->describe($level),
            'config' => [...$round['public'], 'url' => route('games.move', $attempt), 'csrf' => csrf_token()],
            'player' => $player,
        ]);
    }

    /** Una jugada: el motor la comprueba; al terminar se guardan estrellas y avance. */
    public function move(Request $request, GameAttempt $attempt): JsonResponse
    {
        $action = (string) $request->json('action');
        $player = $this->player();
        $mine = $player ? $attempt->player_id === $player->id : ($attempt->player_id === null && $attempt->session_id === $request->session()->getId());
        abort_unless($mine, 403);

        return DB::transaction(function () use ($request, $attempt, $action, $player) {
            $attempt = GameAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            abort_if($attempt->isFinished(), 409, 'Esta partida ya terminó.');

            $engine = $this->games->engine($attempt->game);
            $result = $engine->act($attempt, $action, $request->json()->all());

            if ($result['done'] ?? false) {
                $attempt->seconds = $attempt->elapsed();
                $attempt->finished_at = now();
                $attempt->stars = $engine->stars($attempt);
                $result['finished'] = $this->finish($attempt, $player);
            }
            $attempt->save();

            return response()->json($result);
        });
    }

    /** Guarda el mejor resultado y avisa de los premios que se pueden reclamar. */
    private function finish(GameAttempt $attempt, ?Player $player): array
    {
        $saved = false;
        $prizes = [];
        $points = 0;

        if ($player && $attempt->stars > 0) {
            $progress = GameProgress::query()->lockForUpdate()->firstOrNew(['player_id' => $player->id, 'game' => $attempt->game, 'level' => $attempt->level]);
            $oldStars = $progress->exists ? $progress->stars : 0;
            $progress->fill($progress->exists
                ? ['stars' => max($progress->stars, $attempt->stars), 'best_seconds' => min($progress->best_seconds, $attempt->seconds), 'plays' => $progress->plays + 1]
                : ['stars' => $attempt->stars, 'best_seconds' => $attempt->seconds])->save();
            $saved = true;
            // Puntos de la semana: solo las estrellas nuevas (repetir lo ya ganado no suma).
            $points = $this->rewards->levelPassed($player, $attempt->game, $attempt->level, $oldStars, $attempt->stars);

            $player = $player->fresh(); // estadísticas con este nivel incluido
            $claimed = $player->claims()->pluck('prize_id')->all();
            $prizes = Prize::query()->published()->ordered()->get()
                ->filter(fn (Prize $prize) => ! in_array($prize->id, $claimed, true) && $prize->isReachedBy($player) && $prize->remaining() > 0)
                ->pluck('title')->values()->all();
        }

        $next = $attempt->level + 1;
        $canNext = $attempt->stars > 0 && $next <= GameCatalog::LEVELS && ($player || $next <= GameCatalog::GUEST_LEVELS);

        return [
            'stars' => $attempt->stars,
            'seconds' => $attempt->seconds,
            'saved' => $saved,
            'points' => $points,
            'guest' => ! $player,
            'retry' => route('games.play', [$attempt->game, $attempt->level]),
            'next' => $canNext ? route('games.play', [$attempt->game, $next]) : null,
            'map' => route('games.levels', $attempt->game),
            'prizes' => $prizes,
            'account' => route('games.account'),
            'register' => route('player.register'),
        ];
    }

    private function info(string $game): array
    {
        return $this->games->catalog()->get($game) ?? abort(404);
    }

    private function player(): ?Player
    {
        return Auth::guard('player')->user();
    }

    /** Las partidas abandonadas no se acumulan. */
    private function prune(): void
    {
        if (random_int(1, 50) === 1) {
            GameAttempt::query()->where('created_at', '<', now()->subDays(2))
                ->where(fn ($q) => $q->whereNull('finished_at')->orWhereNull('player_id'))
                ->delete();
        }
    }
}
