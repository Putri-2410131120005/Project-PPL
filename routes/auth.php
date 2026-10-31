<?php

use App\Http\Controllers\Dev\GpkController;
use App\Http\Controllers\Dev\PrincipalController;
use App\Http\Controllers\Dev\TimInklusiController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Middleware\EnsureGoogleStaff;
use Illuminate\Support\Facades\Route;

Route::get('/masuk', [GoogleAuthController::class, 'login'])->name('login');
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

Route::middleware(EnsureGoogleStaff::class.':Admin,Admin / Pengelola')
    ->get('/admin/dashboard', [StaffDashboardController::class, 'admin'])->name('staff.admin.dashboard');
Route::middleware(EnsureGoogleStaff::class.':GPK')
    ->get('/gpk/dashboard', [GpkController::class, 'dashboard'])->name('staff.gpk.dashboard');
Route::middleware(EnsureGoogleStaff::class.':Tim Inklusi')
    ->get('/tim-inklusi/dashboard', [TimInklusiController::class, 'dashboard'])->name('staff.tim-inklusi.dashboard');
Route::middleware(EnsureGoogleStaff::class.':Kepala Sekolah')
    ->get('/kepala-sekolah/dashboard', [PrincipalController::class, 'dashboard'])->name('staff.principal.dashboard');
