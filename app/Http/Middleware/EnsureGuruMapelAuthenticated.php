<?php

namespace App\Http\Middleware;

use App\Services\GuruMapelAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureGuruMapelAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        // Guru mata pelajaran menggunakan guard terpisah dari staf sekolah lainnya.
        $guard = Auth::guard('guru_mapel');

        if (! $guard->check()) {
            return redirect()->guest(route('guru-mapel.login'));
        }

        $guru = $guard->user()->loadMissing('role');
        if ($guru->status_akun !== 'Aktif' || $guru->role?->nama_role !== 'Guru Mapel') {
            // Sesi dibersihkan agar akun yang tidak lagi sesuai tidak dapat melanjutkan akses.
            $guard->logout();
            Auth::guard('staff')->logout();
            $request->session()->forget('guru_note_creation_token');
            $request->session()->regenerate();
            $request->session()->regenerateToken();

            return redirect()->route('guru-mapel.login')
                ->withErrors(['login' => 'Akun Guru Mata Pelajaran tidak aktif atau tidak sesuai.']);
        }

        app(GuruMapelAccess::class)->actor($guru);

        return $next($request);
    }
}
