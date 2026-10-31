<?php

use App\Http\Controllers\Dev\GuruMapelAuthController;
use App\Http\Controllers\Dev\GuruMapelController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Middleware\EnsureGuruMapelAuthenticated;
use Illuminate\Support\Facades\Route;

Route::prefix('guru-mapel')->name('guru-mapel.')->group(function () {
    Route::get('masuk', [GoogleAuthController::class, 'roleLogin'])->defaults('role', 'guru-mapel')->name('login');
    Route::post('masuk', [GuruMapelAuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');

    Route::middleware(EnsureGuruMapelAuthenticated::class)->group(function () {
        Route::post('keluar', [GuruMapelAuthController::class, 'destroy'])->name('logout');
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
        Route::get('referensi/{source}/{id}', [GuruMapelController::class, 'referenceShow'])->whereNumber('id')->name('references.show');
    });
});
