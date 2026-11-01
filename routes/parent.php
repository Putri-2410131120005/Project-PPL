<?php

use App\Http\Controllers\Dev\ParentAuthController;
use App\Http\Controllers\Dev\ParentPortalController;
use App\Http\Middleware\PreventAuthenticatedPageCaching;
use Illuminate\Support\Facades\Route;

// Route portal orang tua dan middleware yang menjaga sesi akun.
Route::prefix('orang-tua')->name('dev.parent.')->group(function () {
    Route::get('masuk', [ParentAuthController::class, 'create'])->name('login');

    Route::middleware(['parent.auth', PreventAuthenticatedPageCaching::class])->group(function () {
        Route::get('ganti-password', [ParentAuthController::class, 'editPassword'])->name('password.edit');
        Route::put('ganti-password', [ParentAuthController::class, 'updatePassword'])->name('password.update');
        Route::get('dashboard', [ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('catatan', [ParentPortalController::class, 'notes'])->name('notes.index');
        Route::get('catatan/{type}/{id}', [ParentPortalController::class, 'note'])
            ->whereIn('type', ['perkembangan', 'pendampingan'])
            ->whereNumber('id')
            ->name('notes.show');
        Route::get('laporan', [ParentPortalController::class, 'reports'])->name('reports.index');
    });
});
