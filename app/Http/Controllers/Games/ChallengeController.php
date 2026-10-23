<?php

namespace App\Http\Controllers\Games;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PrizeClaim;
use App\Services\GameRewards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Reto del día (una respuesta por día) y la confirmación de que el jugador
 * sigue las páginas del Movimiento (requisito para recibir un premio; el
 * equipo lo verifica en persona al entregarlo).
 */
class ChallengeController extends Controller
{
    public function answer(Request $request, GameRewards $rewards): RedirectResponse
    {
        $data = $request->validate(['choice' => ['required', 'string', 'max:120']]);
        $challenge = $rewards->dailyChallenge() ?? abort(404);
        abort_unless(in_array($data['choice'], $challenge['choices'], true), 422);

        $answer = $rewards->answerDaily($this->player(), $data['choice']);

        $message = match (true) {
            $answer === null => 'Ya respondiste el reto de hoy. ¡Vuelve mañana!',
            $answer->correct => '¡Correcto! Ganaste '.GameRewards::DAILY_POINTS." puntos esta semana. ({$challenge['reference']})",
            default => "No era esa. La respuesta es «{$challenge['answer']}» ({$challenge['reference']}). ¡Mañana hay otro reto!",
        };

        $back = $request->input('from') === 'account' ? route('games.account') : route('games.index');

        return redirect()->to($back.'#reto')->with('status', $message);
    }

    /** «Sigo las páginas»: para los premios del ranking semanal (que asigna el equipo). */
    public function declareFollow(Request $request, PrizeClaim $claim): RedirectResponse
    {
        abort_unless($claim->player_id === $this->player()->id && $claim->status === ClaimStatus::Pending, 404);
        $request->validate(['follow' => ['accepted']], ['follow.accepted' => 'Marca la casilla para confirmar que sigues las páginas.']);
        $claim->forceFill(['follow_declared_at' => $claim->follow_declared_at ?? now()])->save();

        return redirect()->to(route('games.account').'#premios')->with('status', 'Gracias. Muestra en tu celular que sigues las páginas cuando recojas tu premio.');
    }

    private function player(): Player
    {
        return Auth::guard('player')->user();
    }
}
