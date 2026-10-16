<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Development-only role selector. It is not application authentication and is
 * never registered in production.
 */
if (app()->environment(['local', 'testing'])) {
    $roles = [
        'admin' => ['label' => 'Admin / Pengelola', 'name' => 'Admin Sekolah', 'initials' => 'AS'],
        'guru-mapel' => ['label' => 'Guru Mata Pelajaran', 'name' => 'Andi Pratama', 'initials' => 'AP'],
        'tim-inklusi' => ['label' => 'Tim Inklusi', 'name' => 'Nisa Lestari', 'initials' => 'NL'],
        'gpk' => ['label' => 'GPK', 'name' => 'Dimas Saputra', 'initials' => 'DS'],
        'kepala-sekolah' => ['label' => 'Kepala Sekolah', 'name' => 'Kepala Sekolah', 'initials' => 'KS'],
        'orang-tua' => ['label' => 'Orang Tua', 'name' => 'Orang Tua Demo', 'initials' => 'OT'],
    ];

    Route::prefix('_dev')->name('dev.')->group(function () use ($roles) {
        Route::get('/', function (Request $request) {
            return $request->session()->has('dev_role')
                ? redirect()->route('dev.preview', $request->session()->get('dev_role'))
                : redirect()->route('dev.login');
        })->name('index');

        Route::get('/login', fn () => view('dev.login', ['roles' => $roles]))->name('login');

        Route::post('/login', function (Request $request) use ($roles) {
            $validated = $request->validate([
                'role' => ['required', 'string', Rule::in(array_keys($roles))],
            ]);

            $request->session()->regenerate();
            $request->session()->put('dev_role', $validated['role']);

            return redirect()->route('dev.preview', $validated['role']);
        })->name('login.store');

        Route::post('/change-role', function (Request $request) {
            $request->session()->forget('dev_role');

            return redirect()->route('dev.login');
        })->name('change-role');

        Route::post('/logout', function (Request $request) {
            $request->session()->forget('dev_role');

            return redirect()->route('dev.login');
        })->name('logout');

        Route::get('/{role}', function (Request $request, string $role) use ($roles) {
            abort_unless(array_key_exists($role, $roles), 404);

            $selectedRole = $request->session()->get('dev_role');

            if (! $selectedRole) {
                return redirect()->route('dev.login');
            }

            if ($selectedRole !== $role) {
                return redirect()->route('dev.preview', $selectedRole);
            }

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
