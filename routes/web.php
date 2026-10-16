<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Preview UI is deliberately local-only. It has no authentication, queries, or
 * state-changing actions; production never registers these routes.
 */
if (app()->environment('local')) {
    $roles = [
        'admin' => ['label' => 'Admin / Pengelola', 'name' => 'Admin Sekolah', 'initials' => 'AS'],
        'guru-mapel' => ['label' => 'Guru Mata Pelajaran', 'name' => 'Andi Pratama', 'initials' => 'AP'],
        'tim-inklusi' => ['label' => 'Tim Inklusi', 'name' => 'Nisa Lestari', 'initials' => 'NL'],
        'gpk' => ['label' => 'GPK', 'name' => 'Dimas Saputra', 'initials' => 'DS'],
        'kepala-sekolah' => ['label' => 'Kepala Sekolah', 'name' => 'Kepala Sekolah', 'initials' => 'KS'],
        'orang-tua' => ['label' => 'Orang Tua', 'name' => 'Orang Tua Demo', 'initials' => 'OT'],
    ];

    Route::prefix('_dev')->name('dev.')->group(function () use ($roles) {
        Route::get('/', fn () => redirect()->route('dev.preview', 'admin'))->name('index');

        Route::get('/{role}', function (string $role) use ($roles) {
            abort_unless(array_key_exists($role, $roles), 404);

            return view(match ($role) {
                'admin' => 'dev.admin-dashboard',
                'guru-mapel' => 'dev.guru-mapel-dashboard',
                default => 'dev.role-preview',
            }, [
                'role' => $role,
                'profile' => $roles[$role],
                'roles' => $roles,
            ]);
        })->name('preview');
    });
}
