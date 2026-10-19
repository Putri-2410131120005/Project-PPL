<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $parent = Auth::guard('parent')->user();

        if (! $parent
            || $parent->status_akun !== 'Aktif'
            || ! $parent->role()->where('nama_role', 'Orang Tua')->exists()) {
            Auth::guard('parent')->logout();

            return redirect()->route('dev.parent.login');
        }

        return $next($request);
    }
}
