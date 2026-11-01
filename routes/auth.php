<?php

use App\Http\Controllers\Dev\ParentAuthController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/masuk', [GoogleAuthController::class, 'login'])->name('login');
Route::post('/masuk', [ParentAuthController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('dev.parent.login.store');
Route::get('/kepala-sekolah/masuk', [GoogleAuthController::class, 'roleLogin'])->defaults('role', 'kepala-sekolah')->name('auth.role.principal.login');
Route::get('/gpk/masuk', [GoogleAuthController::class, 'roleLogin'])->defaults('role', 'gpk')->name('auth.role.gpk.login');
Route::get('/tim-inklusi/masuk', [GoogleAuthController::class, 'roleLogin'])->defaults('role', 'tim-inklusi')->name('auth.role.tim-inklusi.login');
Route::get('/admin/masuk', [GoogleAuthController::class, 'roleLogin'])->defaults('role', 'admin')->name('auth.role.admin.login');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectWithoutRole'])->name('auth.google.redirect');
Route::get('/auth/google/{role}/redirect', [GoogleAuthController::class, 'redirect'])
    ->whereIn('role', ['kepala-sekolah', 'guru-mapel', 'gpk', 'tim-inklusi', 'admin'])
    ->name('auth.google.role.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/keluar', [GoogleAuthController::class, 'logout'])->name('logout');

