<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\GameProgress;
use App\Models\Player;
use App\Models\WeeklyWinner;
use App\Services\GameCatalog;
use App\Services\GameRewards;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Lo que se comparte: la tarjeta de logros de cada jugador (solo su apodo y
 * su avance) y el ranking. Quien desactiva «perfil público» no aparece.
 */
class CommunityController extends Controller
{
    public function profile(GameCatalog $games, string $nickname): View
    {
        $player = Player::query()->listed()->where('nickname', $nickname)->firstOrFail();

        return view('games.profile', ['player' => $player, 'stats' => $player->stats(), 'games' => $games->catalog()]);
    }

    public function ranking(Request $request, GameCatalog $games, GameRewards $rewards): View
    {
        $game = array_key_exists($request->query('juego'), GameCatalog::GAMES) ? $request->query('juego') : null;
        $weekly = ! $game && $request->query('periodo') !== 'siempre';
        $winners = WeeklyWinner::query()->latest('week')->orderBy('position')->limit(18)->get()->groupBy(fn ($w) => $w->week->toDateString())->take(6);

        if ($weekly) {
            return view('games.ranking', [
                'rows' => $rewards->weeklyRanking(GameRewards::week()),
                'weekly' => true,
                'game' => null,
                'games' => $games->catalog(),
                'winners' => $winners,
                'weekEnds' => GameRewards::week()->endOfWeek(Carbon::SUNDAY),
            ]);
        }

        $rows = GameProgress::query()
            ->join('players', 'players.id', '=', 'game_progress.player_id')
            ->where('players.is_active', true)->where('players.is_public', true)
            ->where('game_progress.stars', '>', 0)
            ->when($game, fn ($q) => $q->where('game_progress.game', $game))
            ->groupBy('players.id', 'players.nickname')
            ->selectRaw('players.nickname, sum(game_progress.stars) as stars, count(*) as levels, sum(game_progress.best_seconds) as seconds')
            ->orderByDesc('stars')->orderByDesc('levels')->orderBy('seconds')
            ->limit(25)
            ->get();

        return view('games.ranking', ['rows' => $rows, 'weekly' => false, 'game' => $game, 'games' => $games->catalog(), 'winners' => $winners, 'weekEnds' => null]);
    }

    public function privacy(): View
    {
        return view('games.privacy');
    }
}
