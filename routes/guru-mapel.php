<?php

use App\Http\Controllers\Dev\GuruMapelAuthController;
use App\Http\Controllers\Dev\GuruMapelController;
use App\Http\Middleware\EnsureDevelopmentGuru;
use Illuminate\Support\Facades\Route;

// Required inside the existing local/testing-only _dev group.
Route::prefix('guru-mapel')->name('guru-mapel.')->group(function () {
    Route::get('masuk', [GuruMapelAuthController::class, 'create'])->name('login');
    Route::middleware(EnsureDevelopmentGuru::class)->group(function () {
        Route::get('dashboard', [GuruMapelController::class, 'dashboard'])->name('dashboard');
        Route::get('siswa', [GuruMapelController::class, 'students'])->name('students.index');
        Route::get('siswa/{siswa}', [GuruMapelController::class, 'student'])->whereNumber('siswa')->name('students.show');
        Route::get('siswa/{siswa}/rekomendasi', [GuruMapelController::class, 'recommendations'])->whereNumber('siswa')->name('recommendations.show');
        Route::get('siswa/{siswa}/catatan/tambah', [GuruMapelController::class, 'create'])->whereNumber('siswa')->name('notes.create');
        Route::post('siswa/{siswa}/catatan', [GuruMapelController::class, 'store'])->block(10, 10)->whereNumber('siswa')->name('notes.store');
        Route::get('status-verifikasi', [GuruMapelController::class, 'status'])->name('notes.status');
        Route::get('catatan/{id}', [GuruMapelController::class, 'show'])->whereNumber('id')->name('notes.show');
        Route::get('catatan/{id}/perbaiki', [GuruMapelController::class, 'edit'])->whereNumber('id')->name('notes.revise');
        Route::put('catatan/{id}', [GuruMapelController::class, 'update'])->whereNumber('id')->name('notes.update');
        Route::get('riwayat', [GuruMapelController::class, 'history'])->name('history.index');
        Route::get('riwayat/{source}/{id}', [GuruMapelController::class, 'historyShow'])->whereNumber('id')->name('history.show');
    });
});
