<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonationReceiptController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BibleController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TeachingController;
use App\Livewire\Admin;
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
    Route::get('contacto', 'contact')->name('contact');
});

Route::controller(TeachingController::class)->prefix('ensenanzas')->name('teachings.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->name('show');
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
