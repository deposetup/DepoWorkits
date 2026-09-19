<?php

use App\Http\Controllers\CredentialChangeRequestController;
use App\Http\Controllers\DepotController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/depolar', [DepotController::class, 'index'])->name('depots.index');
    Route::get('/depolar/{depot}', [DepotController::class, 'show'])->name('depots.show');

    // GLN/şifre değişikliği: müşteri sadece talep açabilir
    Route::post('/depolar/talep', [CredentialChangeRequestController::class, 'store'])
        ->name('credential-requests.store');

    Route::middleware('role:admin')->group(function () {
        Route::put('/depolar/{depot}', [DepotController::class, 'update'])->name('depots.update');
        Route::post('/depolar/talep/{changeRequest}/karar', [CredentialChangeRequestController::class, 'review'])
            ->name('credential-requests.review');
    });
});
