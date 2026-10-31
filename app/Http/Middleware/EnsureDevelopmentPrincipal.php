<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureDevelopmentPrincipal
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $guard = Auth::guard('staff');
        if ($guard->check()) {
            $principal = $guard->user()->fresh(['role']);
            $isActivePrincipal = $principal
                && $principal->status_akun === 'Aktif'
                && $principal->google_id !== null
                && $principal->role?->nama_role === 'Kepala Sekolah';

            if ($isActivePrincipal) {
                $request->attributes->set('staff', $principal);

                return $next($request);
            }

            abort(403);
        }

        if (! $request->session()->has('dev_role')) {
            return redirect()->route('dev.login');
        }
        abort_unless($request->session()->get('dev_role') === 'kepala-sekolah', 403);

        return $next($request);
    }
}
