<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GoogleAuthController::class, 'login'])->name('home');

require __DIR__.'/auth.php';
require __DIR__.'/guru-mapel-auth.php';
require __DIR__.'/parent.php';

Route::view('/offline', 'offline')->name('offline');
Route::get('/push/vapid-key', [PushSubscriptionController::class, 'vapidKey'])->name('push.vapid-key');
Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store'])->name('push.subscriptions.store');

// Route modul yang sama dipakai pada local, testing, dan production. Seluruhnya
// dilindungi autentikasi nyata; tidak ada pemilih role atau bypass development.
require __DIR__.'/staff-production.php';
