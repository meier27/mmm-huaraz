<?php

namespace App\Http\Controllers\Games;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Prize;
use App\Models\PrizeClaim;
use App\Models\SiteSetting;
use App\Services\GameCatalog;
use App\Services\GameRewards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * «Mi avance»: estrellas, medallas, premios y su canje, y los datos de la
 * cuenta (incluido borrarla).
 */
class AccountController extends Controller
{
    public function show(GameCatalog $games, GameRewards $rewards): View
    {
        $player = $this->player();
        $claims = $player->claims()->with('prize')->latest()->get();
        $weeklyPoints = $rewards->weeklyPointsOf($player);
        $position = $player->is_public && $weeklyPoints > 0
            ? $rewards->weeklyRanking(GameRewards::week(), 500)->search(fn ($row) => $row->player_id === $player->id)
            : false;

        return view('games.account', [
            'player' => $player,
            'stats' => $player->stats(),
            'weeklyPoints' => $weeklyPoints,
            'weeklyPosition' => $position === false ? null : $position + 1,
            'streak' => GameRewards::currentStreak($player),
            'daily' => $rewards->dailyChallenge(),
            'dailyAnswer' => $rewards->dailyAnswerOf($player),
            'followLinks' => SiteSetting::current()->followLinks(),
            'games' => $games->catalog(),
            'claims' => $claims,
            'prizes' => Prize::query()->published()->ordered()
                ->withCount(['claims' => fn ($q) => $q->where('status', '!=', ClaimStatus::Cancelled->value)])->get()
                ->reject(fn (Prize $prize) => $prize->isWeekly())
                ->map(fn (Prize $prize) => [
                    'prize' => $prize,
                    'progress' => min($prize->progressOf($player), $prize->goal_value),
                    'reached' => $prize->isReachedBy($player),
                    'claim' => $claims->firstWhere('prize_id', $prize->id),
                    'remaining' => $prize->remaining(),
                ]),
        ]);
    }

    /** Reclama un premio alcanzado: genera el código para mostrar en la iglesia. */
    public function claim(Request $request, Prize $prize): RedirectResponse
    {
        $player = $this->player();
        // Requisito: seguir las páginas del Movimiento (se verifica en persona al entregar).
        $mustFollow = SiteSetting::current()->followLinks() !== [];
        $request->validate(['follow' => $mustFollow ? ['accepted'] : []], ['follow.accepted' => 'Para reclamar el premio debes seguir las páginas del Movimiento.']);

        $claim = DB::transaction(function () use ($prize, $player, $mustFollow) {
            // Bloqueo del premio: dos personas no se llevan la última unidad.
            $prize = Prize::query()->published()->lockForUpdate()->findOrFail($prize->id);

            abort_if($prize->isWeekly(), 403, 'Este premio se gana en el ranking semanal.');
            if ($player->claims()->where('prize_id', $prize->id)->exists()) {
                return null;
            }
            abort_unless($prize->isReachedBy($player), 403, 'Aún no alcanzas la meta de este premio.');
            abort_unless($prize->remaining() > 0, 409, 'Este premio ya se agotó.');

            $claim = PrizeClaim::create([
                'prize_id' => $prize->id,
                'player_id' => $player->id,
                'player_nickname' => $player->nickname,
                'code' => PrizeClaim::newCode(),
                'status' => ClaimStatus::Pending,
            ]);

            $claim->forceFill(['follow_declared_at' => $mustFollow ? now() : null])->save();

            return $claim;
        });

        return redirect()->to(route('games.account').'#premios')->with('status', $claim
            ? "¡Premio reclamado! Tu código es {$claim->code}. Muéstralo en la iglesia para recibir «{$prize->title}»."
            : 'Ya reclamaste este premio.');
    }

    public function update(Request $request): RedirectResponse
    {
        $player = $this->player();
        $data = $request->validate([
            'nickname' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[\pL\pN_.-]+$/u', Rule::unique('players', 'nickname')->ignore($player->id)],
            'name' => ['required', 'string', 'max:100'],
            'is_public' => ['boolean'],
        ], ['nickname.regex' => 'El apodo solo puede tener letras, números, punto, guion o guion bajo (sin espacios).', 'nickname.unique' => 'Ese apodo ya está en uso.'],
            ['nickname' => 'apodo', 'name' => 'nombre']);

        $player->update([...$data, 'is_public' => $request->boolean('is_public')]);

        return redirect()->to(route('games.account').'#cuenta')->with('status', 'Datos guardados.');
    }

    /** Borra la cuenta y todo su avance (los canjes ya hechos quedan con el apodo). */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:player']], [], ['password' => 'contraseña']);

        $player = $this->player();
        Auth::guard('player')->logout();
        $player->delete();
        $request->session()->regenerateToken();

        return redirect()->route('games.index')->with('status', 'Tu cuenta y tu avance se eliminaron.');
    }

    private function player(): Player
    {
        return Auth::guard('player')->user();
    }
}
