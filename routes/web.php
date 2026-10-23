<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DoctrineBookController;
use App\Http\Controllers\Admin\DonationReceiptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BibleController;
use App\Http\Controllers\DoctrinePageController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Games\AccountController;
use App\Http\Controllers\Games\ChallengeController;
use App\Http\Controllers\Games\CommunityController;
use App\Http\Controllers\Games\PlayController;
use App\Http\Controllers\Games\PlayerAuthController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TeachingController;
use App\Livewire\Admin;
use App\Services\GameCatalog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitio del MMM Huaraz
|--------------------------------------------------------------------------
*/

Route::controller(SiteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('nosotros', 'about')->name('about');
    Route::get('bethel-tv', 'bethel')->name('bethel');
    Route::get('doctrina', 'doctrine')->name('doctrine');
    Route::get('contacto', 'contact')->name('contact');
});

// Páginas del Doctrinario: solo con enlace firmado y vigente, y solo para este sitio.
Route::get('doctrina/paginas/{book}/{page}', DoctrinePageController::class)
    ->whereNumber(['book', 'page'])->middleware(['signed', 'throttle:240,1'])->name('doctrine.page');

Route::controller(TeachingController::class)->prefix('ensenanzas')->name('teachings.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
});

/*
| Juegos bíblicos por niveles. Cuentas de jugadores: guard «player», separado
| del panel. Las jugadas se comprueban en el servidor (games.move).
*/
Route::prefix('juegos')->group(function () {
    Route::get('/', [PlayController::class, 'index'])->name('games.index');
    Route::controller(CommunityController::class)->name('games.')->group(function () {
        Route::get('ranking', 'ranking')->name('ranking');
        Route::get('privacidad', 'privacy')->name('privacy');
        Route::get('jugador/{nickname}', 'profile')->name('profile');
    });

    Route::controller(PlayerAuthController::class)->middleware('guest:player')->name('player.')->group(function () {
        Route::get('registro', 'create')->name('register');
        Route::post('registro', 'store')->middleware('throttle:login');
        Route::get('ingresar', 'login')->name('login');
        Route::post('ingresar', 'authenticate')->middleware('throttle:login');
        Route::get('recuperar', 'forgot')->name('password.request');
        Route::post('recuperar', 'sendResetLink')->middleware('throttle:6,1')->name('password.email');
        Route::get('restablecer/{token}', 'resetForm')->name('password.reset');
        Route::post('restablecer', 'reset')->middleware('throttle:login')->name('password.update');
    });

    Route::middleware('auth:player')->group(function () {
        Route::post('salir', [PlayerAuthController::class, 'logout'])->name('player.logout');
        Route::controller(AccountController::class)->name('games.')->group(function () {
            Route::get('mi-avance', 'show')->name('account');
            Route::put('mi-avance', 'update')->name('account.update');
            Route::delete('mi-avance', 'destroy')->name('account.destroy');
            Route::post('premios/{prize}/reclamar', 'claim')->middleware('throttle:10,1')->name('claim');
        });
        Route::controller(ChallengeController::class)->name('games.')->group(function () {
            Route::post('reto-del-dia', 'answer')->middleware('throttle:10,1')->name('daily');
            Route::post('canjes/{claim}/sigo-las-paginas', 'declareFollow')->middleware('throttle:10,1')->name('follow');
        });
    });

    Route::controller(PlayController::class)->name('games.')->group(function () {
        Route::post('partida/{attempt}', 'move')->middleware('throttle:240,1')->name('move');
        Route::get('{game}', 'levels')->whereIn('game', array_keys(GameCatalog::GAMES))->name('levels');
        Route::get('{game}/nivel/{level}', 'play')->whereIn('game', array_keys(GameCatalog::GAMES))->whereNumber('level')->name('play');
    });
});

Route::controller(BibleController::class)->prefix('biblia')->name('bible.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('buscar', 'search')->name('search');
    Route::get('{book}/{chapter?}', 'chapter')->whereNumber('chapter')->name('chapter');
    Route::get('{book}/{chapter}/versiculos', 'verseCount')->whereNumber('chapter')->middleware('throttle:60,1')->name('verse-count');
});

/*
|--------------------------------------------------------------------------
| Eventos y sus módulos (programa, sede y donaciones)
|--------------------------------------------------------------------------
*/

Route::get('eventos', [EventController::class, 'index'])->name('events.index');

// URL antiguas de la convención: llevan al evento destacado. Van antes de
// {event:slug}; "convencion" es un slug reservado (ChurchEvent::uniqueSlug).
Route::get('eventos/convencion/{section?}', [EventController::class, 'legacy'])->whereIn('section', ['programa', 'sede', 'donaciones']);
foreach (['programa', 'sede', 'donaciones'] as $old) {
    Route::get($old, [EventController::class, 'legacy'])->defaults('section', $old);
}

Route::prefix('eventos/{event:slug}')->name('events.')->group(function () {
    Route::get('/', [EventController::class, 'show'])->name('show');
    Route::get('programa', [EventController::class, 'program'])->name('program');
    Route::get('sede', [EventController::class, 'venue'])->name('venue');
    Route::get('donaciones', [EventController::class, 'donations'])->name('donations');
    // Secciones retiradas (comidas, hospedaje, turismo): los enlaces ya compartidos llevan al evento.
    Route::get('{retired}', [EventController::class, 'retired'])->whereIn('retired', ['reservas', 'hospedaje', 'turismo']);
});

/*
|--------------------------------------------------------------------------
| Administración
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [LoginController::class, 'create'])->name('login');
    Route::post('admin/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('can:manage-content')->group(function () {
        // Sitio del movimiento
        Route::get('sitio', Admin\SiteSettingsEditor::class)->name('site');
        Route::get('apariencia', Admin\AppearanceEditor::class)->name('appearance');
        Route::get('redes-sociales', Admin\SocialLinksEditor::class)->name('social');
        Route::get('banners', Admin\BannerManager::class)->name('banners');
        Route::get('fotos', Admin\GalleryPhotoManager::class)->name('gallery');
        Route::get('medios', Admin\MediaOutletManager::class)->name('media');
        Route::get('lemas', Admin\MottoManager::class)->name('mottos');
        Route::get('ensenanzas', Admin\TeachingManager::class)->name('teachings');
        Route::get('versiculos', Admin\VerseManager::class)->name('verses');
        Route::get('nosotros', Admin\AboutBlockManager::class)->name('about');
        Route::get('lideres', Admin\LeaderManager::class)->name('leaders');
        Route::get('preguntas', Admin\FaqManager::class)->name('faqs');
        Route::get('juegos', Admin\GameItemManager::class)->name('games');
        Route::get('juegos/premios', Admin\PrizeManager::class)->name('prizes');
        Route::get('juegos/canjes', Admin\PrizeClaimIndex::class)->name('claims');
        Route::get('juegos/jugadores', Admin\PlayerIndex::class)->name('players');
        Route::get('juegos/ranking-semanal', Admin\WeeklyRankingManager::class)->name('weekly');
        Route::controller(DoctrineBookController::class)->prefix('doctrinario')->name('doctrine')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store')->name('.store');
            Route::put('{book}', 'update')->name('.update');
            Route::post('{book}/procesar', 'reprocess')->name('.reprocess');
            Route::post('{book}/indice', 'detectIndex')->name('.index');
            Route::delete('{book}', 'destroy')->name('.destroy');
        });
        Route::get('donaciones', Admin\DonationMethodManager::class)->name('donation-methods');

        // Eventos y sus módulos
        Route::get('eventos', Admin\ChurchEventManager::class)->name('events');
        Route::prefix('eventos/{event}')->name('events.')->group(function () {
            Route::get('/', Admin\EventEditor::class)->name('edit');
            Route::get('programa', Admin\ProgramItemManager::class)->name('program');
            Route::get('programa/dias', Admin\ProgramDayManager::class)->name('program-days');
            Route::get('sedes', Admin\VenueManager::class)->name('venues');
        });
    });

    Route::middleware('can:manage-donations')->group(function () {
        Route::get('aportes', Admin\DonationIndex::class)->name('donations');
        Route::get('aportes/{donation}/constancia', DonationReceiptController::class)->name('donations.receipt');
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::get('usuarios', Admin\UserManager::class)->name('users');
        Route::get('auditoria', Admin\AuditLogIndex::class)->name('audit');
    });
});
