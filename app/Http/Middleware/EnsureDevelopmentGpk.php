<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevelopmentGpk
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->session()->get('dev_role');

        if (! $role) {
            return redirect()->route('dev.login');
        }

        if ($role !== 'gpk') {
            return redirect()->route('dev.preview', $role);
        }

        $gpk = Pengguna::query()
            ->with('role')
            ->whereKey($request->session()->get('dev_pengguna_id'))
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->where('nama_role', 'GPK'))
            ->first();

        if (! $gpk) {
            $request->session()->forget(['dev_role', 'dev_pengguna_id']);

            return redirect()->route('dev.login')
                ->withErrors(['gpk_id' => 'Sesi GPK tidak valid. Pilih kembali akun GPK yang aktif.']);
        }

        $request->session()->put('dev_pengguna_id', $gpk->getKey());
        $request->attributes->set('gpk', $gpk);

        return $next($request);
    }
}
