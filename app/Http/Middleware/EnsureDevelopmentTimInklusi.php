<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevelopmentTimInklusi
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->session()->get('dev_role');

        if (! $role) {
            return redirect()->route('dev.login');
        }

        if ($role !== 'tim-inklusi') {
            return redirect()->route('dev.preview', $role);
        }

        $pendamping = Pengguna::query()
            ->with('role')
            ->whereKey($request->session()->get('dev_pengguna_id'))
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->where('nama_role', 'Tim Inklusi'))
            ->first();

        if (! $pendamping) {
            $request->session()->forget(['dev_role', 'dev_pengguna_id']);

            return redirect()->route('dev.login')
                ->withErrors(['pendamping_id' => 'Sesi Tim Inklusi tidak valid. Pilih kembali akun Tim Inklusi yang aktif.']);
        }

        $request->session()->put('dev_pengguna_id', $pendamping->getKey());
        $request->attributes->set('tim_inklusi', $pendamping);

        return $next($request);
    }
}
