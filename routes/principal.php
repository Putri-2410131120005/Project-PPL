<?php

use App\Http\Controllers\Dev\PrincipalController;
use App\Http\Middleware\EnsureDevelopmentPrincipal;
use Illuminate\Support\Facades\Route;

Route::prefix('kepala-sekolah')->name('principal.')->middleware(EnsureDevelopmentPrincipal::class)->group(function () {
    Route::get('dashboard', [PrincipalController::class, 'dashboard'])->name('dashboard');
    Route::get('siswa', [PrincipalController::class, 'students'])->name('students');
    Route::get('siswa/{siswa}', [PrincipalController::class, 'student'])->whereNumber('siswa')->name('student');
    Route::get('rekap', [PrincipalController::class, 'recaps'])->name('recaps');
    Route::get('rekap/ekspor/{format}', [PrincipalController::class, 'exportRecaps'])->name('recaps.export');
    Route::get('rekap/siswa/{siswa}', [PrincipalController::class, 'verifiedRecap'])->whereNumber('siswa')->name('verified-recap');
    Route::get('rekap/{rekap}', [PrincipalController::class, 'recap'])->whereNumber('rekap')->name('recap');
    Route::get('rekap/{rekap}/semester', [PrincipalController::class, 'semester'])->whereNumber('rekap')->name('semester');
    Route::get('rekap/{rekap}/catatan/{link}', [PrincipalController::class, 'note'])->whereNumber(['rekap', 'link'])->name('note');
    Route::get('rekap/{rekap}/pratinjau', [PrincipalController::class, 'preview'])->whereNumber('rekap')->name('recap.preview');
    Route::get('rekap/{rekap}/ekspor/{format}', [PrincipalController::class, 'exportRecap'])->whereNumber('rekap')->name('recap.export');
    Route::get('arsip', [PrincipalController::class, 'archives'])->name('archives');
    Route::get('laporan', [PrincipalController::class, 'period'])->name('period');
    Route::get('laporan/pratinjau', [PrincipalController::class, 'preview'])->name('period.preview');
    Route::get('laporan/ekspor/{format}', [PrincipalController::class, 'exportPeriod'])->name('period.export');
});
