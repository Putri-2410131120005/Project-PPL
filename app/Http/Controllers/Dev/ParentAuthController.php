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
        // Mengarahkan orang tua yang sudah login ke halaman yang sesuai.
        if (Auth::guard('parent')->check()) {
            return $this->redirectAfterLogin(Auth::guard('parent')->user());
        }

        return redirect()->route('login');
    }

    public function store(Request $request): RedirectResponse
    {
        // Memvalidasi dan mencocokkan kredensial akun orang tua.
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
        Auth::guard('staff')->logout();
        Auth::guard('guru_mapel')->logout();
        $request->session()->regenerate();

        return $this->redirectAfterLogin($parent);
    }

    public function editPassword(): View
    {
        // Menampilkan form penggantian password sementara.
        return view('dev.parent-auth.change-password', [
            'parent' => Auth::guard('parent')->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        // Menyimpan password baru dan menyelesaikan proses login pertama.
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

        return redirect()->route('dev.parent.dashboard')
            ->with('success', 'Password berhasil diubah. Gunakan password baru untuk login berikutnya.');
    }

    private function redirectAfterLogin(Pengguna $parent): RedirectResponse
    {
        // Akun dengan password sementara wajib melewati halaman penggantian password.
        if ($parent->wajib_ganti_password) {
            return redirect()->route('dev.parent.password.edit');
        }

        return redirect()->route('dev.parent.dashboard');
    }
}
