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
        // Memvalidasi kredensial akun yang memang diizinkan menggunakan password.
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $account = Pengguna::query()
            ->with('role')
            ->where('username', $credentials['username'])
            ->where('status_akun', 'Aktif')
            ->where(function ($accounts): void {
                $accounts->whereHas('role', fn ($roles) => $roles->where('nama_role', 'Orang Tua'))
                    ->orWhere(function ($superAdmins): void {
                        $superAdmins
                            ->where('is_super_admin', true)
                            ->whereHas('role', fn ($roles) => $roles->whereIn('nama_role', ['Admin', 'Admin / Pengelola']));
                    });
            })
            ->first();

        if (! $account || ! Hash::check($credentials['password'], $account->password)) {
            throw ValidationException::withMessages([
                'username' => 'Username atau password tidak sesuai.',
            ]);
        }

        Auth::guard('staff')->logout();
        Auth::guard('guru_mapel')->logout();
        Auth::guard('parent')->logout();

        if ($account->role?->nama_role === 'Orang Tua') {
            Auth::guard('parent')->login($account);
        } else {
            Auth::guard('staff')->login($account);
        }
        $request->session()->regenerate();

        if ($account->role?->nama_role === 'Orang Tua') {
            return $this->redirectAfterLogin($account);
        }

        return redirect()->route('dev.preview');
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
