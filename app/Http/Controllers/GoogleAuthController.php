<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class GoogleAuthController extends Controller
{
    // Controller ini menangani login Google, pemetaan role, callback, dan logout.
    private const STAFF_LOGIN_ROLES = [
        'kepala-sekolah',
        'guru-mapel',
        'gpk',
        'tim-inklusi',
        'admin',
    ];

    private const INTERNAL_ROLES = [
        'Admin',
        'Admin / Pengelola',
        'GPK',
        'Tim Inklusi',
        'Guru Mapel',
        'Kepala Sekolah',
    ];

    public function login(): View|RedirectResponse
    {
        // Menampilkan login utama atau langsung meneruskan sesi yang sudah aktif.
        if (Auth::guard('staff')->check()) {
            return $this->redirectFor(Auth::guard('staff')->user()->loadMissing('role'));
        }

        if (Auth::guard('parent')->check()) {
            return redirect()->route('dev.parent.dashboard');
        }

        return view('auth.login', $this->loginViewData());
    }

    public function roleLogin(string $role): View|RedirectResponse
    {
        $this->roleContext($role);

        return redirect()->route('login');
    }

    public function redirectWithoutRole(): RedirectResponse
    {
        if (! $this->googleConfigured()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google Login belum dikonfigurasi oleh pengelola sistem.',
            ]);
        }

        if ($account = Auth::guard('staff')->user()?->loadMissing('role')) {
            return $this->redirectFor($account);
        }

        return Socialite::driver('google')->redirect();
    }

    public function redirect(string $role): RedirectResponse
    {
        $this->roleContext($role);

        return $this->redirectWithoutRole();
    }

    public function callback(Request $request): RedirectResponse
    {
        // Memproses identitas Google dan mengarahkan pengguna sesuai role database.
        if (! $this->googleConfigured()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google Login belum dikonfigurasi oleh pengelola sistem.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $account = $this->resolveAccount($googleUser);
        } catch (ValidationException $exception) {
            return redirect()->route('login')->withErrors($exception->errors());
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors([
                'google' => 'Sesi Google Login tidak valid atau sudah kedaluwarsa. Silakan coba kembali.',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Google Login callback gagal.', [
                'exception' => $exception::class,
            ]);

            return redirect()->route('login')->withErrors([
                'google' => 'Google Login tidak dapat diproses saat ini. Silakan coba kembali.',
            ]);
        }

        Auth::guard('staff')->login($account);
        Auth::guard('parent')->logout();
        if ($account->role?->nama_role === 'Guru Mapel') {
            Auth::guard('guru_mapel')->login($account);
        } else {
            Auth::guard('guru_mapel')->logout();
        }
        $request->session()->forget('guru_note_creation_token');
        $request->session()->regenerate();

        return $this->redirectFor($account);
    }

    public function logout(Request $request): RedirectResponse
    {
        // Mengakhiri seluruh guard dan membersihkan sesi aplikasi.
        Auth::guard('staff')->logout();
        Auth::guard('guru_mapel')->logout();
        Auth::guard('parent')->logout();
        $request->session()->forget('guru_note_creation_token');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function resolveAccount(SocialiteUser $googleUser): Pengguna
    {
        $googleId = trim((string) $googleUser->getId());
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $verified = filter_var(
            data_get($googleUser->user, 'email_verified', data_get($googleUser->user, 'verified_email', false)),
            FILTER_VALIDATE_BOOL
        );

        if ($googleId === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $verified) {
            throw ValidationException::withMessages([
                'google' => 'Identitas Google tidak valid atau email Google belum terverifikasi.',
            ]);
        }

        try {
            return DB::transaction(function () use ($googleId, $email): Pengguna {
                $account = Pengguna::query()
                    ->with('role')
                    ->where('google_id', $googleId)
                    ->lockForUpdate()
                    ->first();

                if (! $account) {
                    $account = Pengguna::query()
                        ->with('role')
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->lockForUpdate()
                        ->first();

                    if ($account && $account->google_id === null) {
                        $this->ensureAllowed($account);
                        $account->forceFill(['google_id' => $googleId])->save();
                    }
                }

                if (! $account) {
                    throw ValidationException::withMessages([
                        'google' => 'Email Google belum terdaftar. Hubungi Super Admin sekolah.',
                    ]);
                }

                $account->refresh()->load('role');
                if (! hash_equals((string) $account->google_id, $googleId)) {
                    throw ValidationException::withMessages([
                        'google' => 'Akun telah terhubung dengan identitas Google lain. Hubungi Super Admin untuk pemulihan akun.',
                    ]);
                }

                $this->ensureAllowed($account);

                return $account;
            }, 3);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'google' => 'Akun Google baru saja dihubungkan pada sesi lain. Silakan coba kembali atau hubungi Super Admin.',
            ]);
        }
    }

    private function ensureAllowed(Pengguna $account): void
    {
        $role = $account->role?->nama_role;
        if ($account->status_akun !== 'Aktif' || ! in_array($role, self::INTERNAL_ROLES, true)) {
            throw ValidationException::withMessages([
                'google' => 'Akun tidak aktif atau tidak memiliki akses sebagai staf sekolah.',
            ]);
        }

        if (in_array($role, ['Admin', 'Admin / Pengelola'], true) && ! $account->is_super_admin) {
            throw ValidationException::withMessages([
                'google' => 'Akses Super Admin tidak tersedia untuk akun ini.',
            ]);
        }

    }

    private function redirectFor(Pengguna $account): RedirectResponse
    {
        $route = match ($account->role?->nama_role) {
            'Admin', 'Admin / Pengelola' => 'dev.preview',
            'GPK' => 'dev.gpk.dashboard',
            'Tim Inklusi' => 'dev.tim-inklusi.dashboard',
            'Guru Mapel' => 'guru-mapel.dashboard',
            'Kepala Sekolah' => 'dev.principal.dashboard',
            default => 'login',
        };

        return redirect()->route($route);
    }

    private function googleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function roleContext(string $role): void
    {
        abort_unless(in_array($role, self::STAFF_LOGIN_ROLES, true), 404);
    }

    private function loginViewData(): array
    {
        return ['googleConfigured' => $this->googleConfigured()];
    }
}
