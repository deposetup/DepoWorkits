<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CredentialChangeRequestController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\ItsNotificationController;
use App\Http\Controllers\PtsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/giris', [LoginController::class, 'create'])->name('login');
    Route::post('/giris', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/cikis', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/depolar', [DepotController::class, 'index'])->name('depots.index');
    Route::get('/depolar/{depot}', [DepotController::class, 'show'])->name('depots.show');

    // GLN/şifre değişikliği: müşteri sadece talep açabilir
    Route::post('/depolar/talep', [CredentialChangeRequestController::class, 'store'])
        ->name('credential-requests.store');

    // İTS bildirimleri: şimdilik sadece Mal Alım ve Mal İade destekleniyor
    Route::get('/its-bildirimleri', [ItsNotificationController::class, 'index'])->name('its-notifications.index');
    Route::get('/its-bildirimleri/olustur/{type}', [ItsNotificationController::class, 'create'])
        ->where('type', 'alim|iptal_iade')
        ->name('its-notifications.create');
    Route::post('/its-bildirimleri', [ItsNotificationController::class, 'store'])->name('its-notifications.store');

    // PTS paket sorgulama
    Route::get('/pts', [PtsController::class, 'index'])->name('pts.index');
    Route::post('/pts/sorgula', [PtsController::class, 'search'])->name('pts.search');

    Route::middleware('role:admin')->group(function () {
        Route::put('/depolar/{depot}', [DepotController::class, 'update'])->name('depots.update');
        Route::post('/depolar/talep/{changeRequest}/karar', [CredentialChangeRequestController::class, 'review'])
            ->name('credential-requests.review');
    });
});
