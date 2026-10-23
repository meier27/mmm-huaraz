<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Models\DailyAnswer;
use App\Models\GamePoint;
use App\Models\Player;
use App\Models\Prize;
use App\Models\PrizeClaim;
use App\Models\WeeklyWinner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Dinámicas de los juegos: puntos de la semana, reto del día, racha y cierre
 * del ranking semanal. Semanas de lunes a domingo en hora de Lima.
 *
 * Puntos: 10 por cada estrella nueva en un nivel (repetir un nivel ya
 * superado no suma), 20 por acertar el reto del día y un bono de racha.
 */
class GameRewards
{
    public const POINTS_PER_STAR = 10;

    public const DAILY_POINTS = 20;

    /** Días seguidos => bono. */
    public const STREAK_BONUS = [3 => 15, 7 => 40, 14 => 80, 30 => 150];

    /** Puestos premiados cada semana. */
    public const WINNERS = 3;

    /** Semanas que debe esperar un ganador para volver a ganar (máximo una vez al mes). */
    public const WIN_COOLDOWN_WEEKS = 4;

    public function __construct(private readonly GameCatalog $games) {}

    public static function week(?Carbon $date = null): Carbon
    {
        return ($date ?? now())->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    /** Suma los puntos de las estrellas nuevas de un nivel y cuenta el día para la racha. */
    public function levelPassed(Player $player, string $game, int $level, int $oldStars, int $newStars): int
    {
        $gained = max(0, $newStars - $oldStars) * self::POINTS_PER_STAR;
        if ($gained) {
            $this->award($player, $gained, 'level', (self::gameTitle($game))." · nivel {$level}");
        }
        $this->touchStreak($player);

        return $gained;
    }

    // ----- Reto del día -----

    /**
     * La pregunta del día: la misma para todos (elegida a partir de la fecha),
     * con las opciones en el mismo orden para todos.
     *
     * @return array{prompt: string, choices: list<string>, answer: string, reference: string}|null
     */
    public function dailyChallenge(?Carbon $day = null): ?array
    {
        $questions = $this->games->questions();
        if ($questions->isEmpty()) {
            return null;
        }

        $date = ($day ?? today())->toDateString();
        $random = new Randomizer(new Mt19937(crc32('reto-'.$date)));
        $question = $questions[$random->getInt(0, $questions->count() - 1)];

        return [...$question, 'choices' => $random->shuffleArray([$question['answer'], ...$question['options']])];
    }

    public function dailyAnswerOf(Player $player): ?DailyAnswer
    {
        return DailyAnswer::query()->where('player_id', $player->id)->whereDate('day', today())->first();
    }

    /** Responde el reto del día (un intento). Devuelve null si ya respondió hoy. */
    public function answerDaily(Player $player, string $choice): ?DailyAnswer
    {
        $challenge = $this->dailyChallenge() ?? abort(404);

        return DB::transaction(function () use ($player, $choice, $challenge) {
            Player::query()->lockForUpdate()->find($player->id); // un intento, aunque se envíe dos veces
            if ($this->dailyAnswerOf($player)) {
                return null;
            }

            $answer = DailyAnswer::create(['player_id' => $player->id, 'day' => today(), 'correct' => $choice === $challenge['answer']]);
            if ($answer->correct) {
                $this->award($player, self::DAILY_POINTS, 'daily', 'Reto del día');
            }
            $this->touchStreak($player);

            return $answer;
        });
    }

    // ----- Racha -----

    /** Cuenta hoy como día jugado: suma a la racha (o la reinicia) y da el bono si toca. */
    public function touchStreak(Player $player): void
    {
        $player->refresh();
        $today = today();
        $last = $player->last_active_on ? Carbon::parse($player->last_active_on) : null;

        if ($last?->isSameDay($today)) {
            return;
        }

        $streak = $last?->isSameDay($today->copy()->subDay()) ? $player->streak_days + 1 : 1;
        $player->forceFill(['streak_days' => $streak, 'last_active_on' => $today->toDateString()])->save();

        if ($bonus = self::STREAK_BONUS[$streak] ?? 0) {
            $this->award($player, $bonus, 'streak', "Racha de {$streak} días");
        }
    }

    /** Racha vigente: si ayer no jugó (ni hoy), ya se cortó. */
    public static function currentStreak(Player $player): int
    {
        $last = $player->last_active_on ? Carbon::parse($player->last_active_on) : null;

        return $last && $last->gte(today()->subDay()) ? (int) $player->streak_days : 0;
    }

    // ----- Ranking semanal -----

    /**
     * Ranking de una semana (solo perfiles públicos y activos). Empate: gana
     * quien llegó antes a su puntaje.
     *
     * @return Collection<int, object{player_id: int, nickname: string, points: int}>
     */
    public function weeklyRanking(Carbon $week, int $limit = 25): Collection
    {
        return GamePoint::query()
            ->join('players', 'players.id', '=', 'game_points.player_id')
            ->where('players.is_active', true)->where('players.is_public', true)
            ->whereDate('game_points.week', $week->toDateString())
            ->groupBy('players.id', 'players.nickname')
            ->selectRaw('players.id as player_id, players.nickname, sum(game_points.points) as points, max(game_points.created_at) as reached_at')
            ->orderByDesc('points')->orderBy('reached_at')
            ->limit($limit)
            ->get();
    }

    public function weeklyPointsOf(Player $player, ?Carbon $week = null): int
    {
        return (int) GamePoint::query()->where('player_id', $player->id)
            ->whereDate('week', self::week($week)->toDateString())->sum('points');
    }

    public function isClosed(Carbon $week): bool
    {
        return WeeklyWinner::query()->whereDate('week', $week->toDateString())->exists();
    }

    /**
     * Candidatos a ganar: los primeros del ranking que no ganaron en las
     * últimas semanas (para que el premio llegue a más gente).
     */
    public function candidates(Carbon $week): Collection
    {
        $recent = WeeklyWinner::query()
            ->whereDate('week', '<', $week->toDateString())
            ->whereDate('week', '>=', $week->copy()->subWeeks(self::WIN_COOLDOWN_WEEKS)->toDateString())
            ->pluck('player_id')->filter()->all();

        return $this->weeklyRanking($week, 50)->reject(fn ($row) => in_array($row->player_id, $recent, true))->take(self::WINNERS)->values();
    }

    /**
     * Cierra una semana ya terminada: registra a los ganadores y les genera el
     * código de canje del premio elegido para cada puesto.
     *
     * @param  array<int, ?int>  $prizes  puesto => id del premio (o null: sin premio)
     * @return Collection<int, WeeklyWinner>
     */
    public function closeWeek(Carbon $week, array $prizes, ?int $closedBy): Collection
    {
        abort_if($week->copy()->endOfWeek(Carbon::SUNDAY)->isFuture(), 422, 'La semana aún no termina.');

        return DB::transaction(function () use ($week, $prizes, $closedBy) {
            abort_if($this->isClosed($week), 409, 'Esa semana ya se cerró.');

            return $this->candidates($week)->values()->map(function ($row, $i) use ($week, $prizes, $closedBy) {
                $position = $i + 1;
                $claim = null;

                if ($prizeId = $prizes[$position] ?? null) {
                    $prize = Prize::query()->lockForUpdate()->findOrFail($prizeId);
                    abort_unless($prize->remaining() > 0, 409, "No quedan unidades de «{$prize->title}».");
                    $claim = PrizeClaim::create([
                        'prize_id' => $prize->id,
                        'player_id' => $row->player_id,
                        'player_nickname' => $row->nickname,
                        'code' => PrizeClaim::newCode(),
                        'status' => ClaimStatus::Pending,
                    ]);
                }

                return WeeklyWinner::create([
                    'week' => $week->toDateString(),
                    'position' => $position,
                    'player_id' => $row->player_id,
                    'player_nickname' => $row->nickname,
                    'points' => (int) $row->points,
                    'prize_claim_id' => $claim?->id,
                    'closed_by' => $closedBy,
                ]);
            });
        });
    }

    private function award(Player $player, int $points, string $reason, string $detail): void
    {
        GamePoint::create(['player_id' => $player->id, 'points' => $points, 'reason' => $reason, 'detail' => mb_substr($detail, 0, 60), 'week' => self::week()->toDateString()]);
    }

    private static function gameTitle(string $game): string
    {
        return GameCatalog::GAMES[$game][1] ?? $game;
    }
}
