<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Cuentas de los jugadores (guard «player»): registro (solo mayores de 14
 * años), inicio de sesión y recuperación de la contraseña por correo.
 */
class PlayerAuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('games.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nickname' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[\pL\pN_.-]+$/u', Rule::unique('players', 'nickname')],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('players', 'email')],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'age' => ['accepted'],
            'terms' => ['accepted'],
        ], [
            'nickname.regex' => 'El apodo solo puede tener letras, números, punto, guion o guion bajo (sin espacios).',
            'nickname.unique' => 'Ese apodo ya está en uso. Prueba con otro.',
            'email.unique' => 'Ya hay una cuenta con ese correo. Inicia sesión o recupera tu contraseña.',
            'age.accepted' => 'Para crear una cuenta debes tener 14 años o más.',
            'terms.accepted' => 'Debes aceptar el aviso de privacidad.',
        ], ['nickname' => 'apodo', 'name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña']);

        $player = new Player(['nickname' => $data['nickname'], 'name' => $data['name'], 'email' => Str::lower($data['email']), 'password' => $data['password']]);
        $player->forceFill(['age_confirmed_at' => now(), 'terms_accepted_at' => now()])->save();

        Auth::guard('player')->login($player, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('games.account'))->with('status', "¡Bienvenido, {$player->nickname}! Tu avance ya se guarda.");
    }

    public function login(): View
    {
        return view('games.auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $key = 'player|'.Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages(['email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        // Las cuentas bloqueadas por el equipo no pueden entrar.
        if (! Auth::guard('player')->attempt([...$credentials, 'email' => Str::lower($credentials['email']), 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('games.account'));
    }

    public function logout(Request $request): RedirectResponse
    {
        // Solo la sesión del jugador (si en el mismo navegador hay sesión del panel, sigue).
        Auth::guard('player')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('games.index')->with('status', 'Cerraste sesión. ¡Vuelve pronto!');
    }

    public function forgot(): View
    {
        return view('games.auth.forgot');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        // Misma respuesta exista o no la cuenta (no se revela quién está registrado).
        Password::broker('players')->sendResetLink(['email' => Str::lower($request->string('email'))]);

        return back()->with('status', __('passwords.sent'));
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('games.auth.reset', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [], ['password' => 'contraseña']);

        $status = Password::broker('players')->reset(
            [...$request->only('password', 'password_confirmation', 'token'), 'email' => Str::lower($request->string('email'))],
            function (Player $player, string $password) {
                $player->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($player));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('player.login')->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
