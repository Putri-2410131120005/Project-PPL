<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class GuruMapelAuthController extends Controller
{
    public function create(Request $request)
    {
        if ($request->routeIs('dev.guru-mapel.login')) {
            return redirect()->route('dev.login');
        }

        if (Auth::guard('guru_mapel')->check()) {
            return redirect()->route('guru-mapel.dashboard');
        }

        return view('dev.guru-mapel.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $guru = Pengguna::query()
            ->with('role')
            ->where(function ($query) use ($credentials) {
                $query->where('username', $credentials['login'])
                    ->orWhere('email', $credentials['login']);
            })
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($role) => $role->where('nama_role', 'Guru Mapel'))
            ->first();

        if (! $guru || ! Hash::check($credentials['password'], $guru->getAuthPassword())) {
            throw ValidationException::withMessages([
                'login' => 'Username/email atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        Auth::guard('guru_mapel')->login($guru);

        return redirect()->intended(route('guru-mapel.dashboard'));
    }

    public function destroy(Request $request)
    {
        $googleSession = Auth::guard('staff')->check();
        Auth::guard('guru_mapel')->logout();
        if ($googleSession) {
            Auth::guard('staff')->logout();
        }
        $request->session()->forget('guru_note_creation_token');
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route($googleSession ? 'login' : 'guru-mapel.login');
    }
}
