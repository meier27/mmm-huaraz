<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

// El .env se lee por petición ($_SERVER/$_ENV), sin putenv(): en Apache para
// Windows (XAMPP, multihilo) el entorno del proceso es compartido y otra
// petición podía borrar APP_KEY a mitad de camino («No application encryption key»).
Env::disablePutenv();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        // Los jugadores (/juegos) tienen su propio inicio de sesión, separado del panel.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('juegos', 'juegos/*') ? route('player.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('juegos', 'juegos/*') ? route('games.account') : route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
