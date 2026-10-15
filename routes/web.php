<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PageController;
use App\Livewire\Admin;
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
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::get('usuarios', Admin\UserManager::class)->name('users');
        Route::get('auditoria', Admin\AuditLogIndex::class)->name('audit');
    });
});
