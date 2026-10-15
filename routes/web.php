<?php

use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReservationController;
use App\Livewire\Admin;
use App\Livewire\ReservationChangeForm;
use App\Livewire\ReservationForm;
use Illuminate\Support\Facades\Route;

Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('programa', 'program')->name('program');
    Route::get('sede', 'venue')->name('venue');
    Route::get('donaciones', 'donations')->name('donations');
    Route::get('hospedaje', 'accommodations')->name('accommodations');
    Route::get('turismo', 'tourism')->name('tourism');
    Route::get('contacto', 'contact')->name('contact');
});

Route::get('reservas', ReservationForm::class)->name('dishes.index');

Route::middleware('throttle:reservation-lookup')->group(function () {
    Route::get('reserva/{token}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('reserva/{token}/qr.svg', [ReservationController::class, 'qr'])->name('reservations.qr');
    Route::get('reserva/{token}/cambiar', ReservationChangeForm::class)->name('reservations.change');
});

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [LoginController::class, 'create'])->name('login');
    Route::post('admin/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('can:manage-content')->group(function () {
        Route::get('convencion', Admin\EventSettingsEditor::class)->name('settings');
        Route::get('programa', Admin\ProgramItemManager::class)->name('program');
        Route::get('programa/dias', Admin\ProgramDayManager::class)->name('program-days');
        Route::get('sedes', Admin\VenueManager::class)->name('venues');
        Route::get('donaciones', Admin\DonationMethodManager::class)->name('donation-methods');
        Route::get('hospedaje', Admin\AccommodationManager::class)->name('accommodations');
        Route::get('turismo', Admin\TouristPlaceManager::class)->name('tourist-places');
        Route::get('preguntas', Admin\FaqManager::class)->name('faqs');
        Route::get('banners', Admin\BannerManager::class)->name('banners');
        Route::get('platos', Admin\DishManager::class)->name('dishes');
        Route::get('platos/ofertas', Admin\DishOfferingManager::class)->name('dish-offerings');
    });

    Route::middleware('can:manage-reservations')->group(function () {
        Route::get('reservas', Admin\ReservationIndex::class)->name('reservations');
        Route::get('reservas/{reservation}', [AdminReservationController::class, 'show'])
            ->middleware('can:view,reservation')
            ->name('reservations.show');
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::get('usuarios', Admin\UserManager::class)->name('users');
        Route::get('auditoria', Admin\AuditLogIndex::class)->name('audit');
    });
});
