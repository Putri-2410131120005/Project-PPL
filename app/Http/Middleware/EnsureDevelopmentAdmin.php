<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevelopmentAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Auth::guard('staff')->user()?->fresh(['role']);
        if ($staff) {
            abort_unless(
                $staff->status_akun === 'Aktif'
                && $staff->google_id !== null
                && $staff->is_super_admin
                && in_array($staff->role?->nama_role, ['Admin', 'Admin / Pengelola'], true),
                403,
            );
            $request->attributes->set('staff', $staff);

            return $next($request);
        }

        $role = $request->session()->get('dev_role');

        if (! $role) {
            return redirect()->route('dev.login');
        }

        if ($role !== 'admin') {
            return redirect()->route('dev.preview', $role);
        }

        if ($request->session()->has('dev_pengguna_id')) {
            $valid = Pengguna::query()->whereKey($request->session()->get('dev_pengguna_id'))
                ->where('is_super_admin', true)->where('status_akun', 'Aktif')->exists();
            if (! $valid) {
                $request->session()->forget(['dev_role', 'dev_pengguna_id']);

                return redirect()->route('dev.login')->withErrors(['role' => 'Sesi Super Admin tidak lagi valid.']);
            }
        }

        return $next($request);
    }
}
