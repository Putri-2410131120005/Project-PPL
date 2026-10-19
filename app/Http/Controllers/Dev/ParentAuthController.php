<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ParentAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('parent')->check()) {
            return $this->redirectAfterLogin(Auth::guard('parent')->user());
        }

        return view('dev.parent-auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $parent = Pengguna::query()
            ->with('role')
            ->where('username', $credentials['username'])
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($roles) => $roles->where('nama_role', 'Orang Tua'))
            ->first();

        if (! $parent || ! Hash::check($credentials['password'], $parent->password)) {
            throw ValidationException::withMessages([
                'username' => 'Username atau password tidak sesuai.',
            ]);
        }

        Auth::guard('parent')->login($parent);
        $request->session()->regenerate();
        $request->session()->forget('dev_role');

        return $this->redirectAfterLogin($parent);
    }

    public function editPassword(): View
    {
        return view('dev.parent-auth.change-password', [
            'parent' => Auth::guard('parent')->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        /** @var Pengguna $parent */
        $parent = Auth::guard('parent')->user();

        if (Hash::check($data['password'], $parent->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password baru harus berbeda dari password sementara atau password sebelumnya.',
            ]);
        }

        $parent->update([
            'password' => Hash::make($data['password']),
            'wajib_ganti_password' => false,
            'password_diubah_pada' => now(),
        ]);

        $request->session()->put('dev_role', 'orang-tua');

        return redirect()->route('dev.preview', 'orang-tua')
            ->with('success', 'Password berhasil diubah. Gunakan password baru untuk login berikutnya.');
    }

    private function redirectAfterLogin(Pengguna $parent): RedirectResponse
    {
        if ($parent->wajib_ganti_password) {
            return redirect()->route('dev.parent.password.edit');
        }

        session()->put('dev_role', 'orang-tua');

        return redirect()->route('dev.preview', 'orang-tua');
    }
}
