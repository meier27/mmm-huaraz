<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\EventSetting;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        $this->configureGates();
        $this->configureRateLimiting();

        View::composer(['components.layouts.public', 'pages.*', 'reservations.*'], fn ($view) => $view->with('settings', EventSetting::current()));
    }

    private function configureGates(): void
    {
        Gate::define('access-admin', fn (User $user) => $user->is_active);
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        Gate::define('manage-content', fn (User $user) => $user->hasRole(Role::Admin, Role::Editor));
        Gate::define('manage-reservations', fn (User $user) => $user->can('viewAny', Reservation::class));
    }

    private function configureRateLimiting(): void
    {
        // Límite global por IP; el límite por cuenta lo aplica LoginController.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        // Consulta de reservas por token: dificulta cualquier intento de enumeración.
        RateLimiter::for('reservation-lookup', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
