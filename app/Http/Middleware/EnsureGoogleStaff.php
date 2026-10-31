<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGoogleStaff
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $guard = Auth::guard('staff');
        if (! $guard->check()) {
            return redirect()->guest(route('login'));
        }

        $account = $guard->user()->fresh(['role']);
        $role = $account?->role?->nama_role;
        $valid = $account
            && $account->status_akun === 'Aktif'
            && $account->google_id !== null
            && ($roles === [] || in_array($role, $roles, true));

        if (in_array($role, ['Admin', 'Admin / Pengelola'], true)) {
            $valid = $valid && $account->is_super_admin;
        }

        if (! $valid) {
            $guard->logout();
            Auth::guard('guru_mapel')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'google' => 'Sesi tidak lagi memiliki akses. Silakan masuk kembali.',
            ]);
        }

        $request->attributes->set('staff', $account);
        if ($role === 'GPK') {
            $request->attributes->set('gpk', $account);
        }
        if ($role === 'Tim Inklusi') {
            $request->attributes->set('tim_inklusi', $account);
        }

        return $next($request);
    }
}
