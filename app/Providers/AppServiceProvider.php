<?php

namespace App\Providers;

use App\Enums\Role;
use App\Livewire\Uploads\ShortNameFileUploadController;
use App\Models\ChurchEvent;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Features\SupportFileUploads\FileUploadController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Nombres temporales cortos: evita fallos con fotos de nombre largo en Windows.
        $this->app->bind(FileUploadController::class, ShortNameFileUploadController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // Paginador del sitio público (los del panel usan el de Livewire).
        Paginator::defaultView('pagination.brand');

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        $this->configureGates();
        $this->configureRateLimiting();

        // $site: identidad y contacto del movimiento (en todas las vistas públicas).
        View::composer(['components.layouts.public', 'pages.*', 'teachings.*', 'bible.*', 'events.*'], fn ($view) => $view->with('site', SiteSetting::current()));

        // Evento destacado para el pie de página: solo el layout, una consulta por página.
        View::composer('components.layouts.public', fn ($view) => $view->with('featuredEvent', ChurchEvent::featured()));
    }

    private function configureGates(): void
    {
        Gate::define('access-admin', fn (User $user) => $user->is_active);
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        Gate::define('manage-content', fn (User $user) => $user->hasRole(Role::Admin, Role::Editor));
        // Verificar aportes y ver comprobantes: administración y tesorería.
        Gate::define('manage-donations', fn (User $user) => $user->hasRole(Role::Admin, Role::Cashier));
    }

    private function configureRateLimiting(): void
    {
        // Límite global por IP; el límite por cuenta lo aplica LoginController.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }
}
