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
    private const LOGIN_CONTEXT_SESSION = 'google_login_role';

    private const ROLE_CONTEXTS = [
        'kepala-sekolah' => [
            'page_title' => 'Masuk Kepala Sekolah',
            'form_title' => 'Selamat datang, Kepala Sekolah',
            'description' => 'Masuk menggunakan akun Google Kepala Sekolah yang telah didaftarkan oleh Super Admin.',
            'label' => 'Kepala Sekolah',
            'database_roles' => ['Kepala Sekolah'],
            'login_route' => 'auth.role.principal.login',
        ],
        'guru-mapel' => [
            'page_title' => 'Masuk Guru Mata Pelajaran',
            'form_title' => 'Selamat datang, Guru Mata Pelajaran',
            'description' => 'Masuk menggunakan akun Google Guru Mata Pelajaran yang telah didaftarkan oleh Super Admin.',
            'label' => 'Guru Mata Pelajaran',
            'database_roles' => ['Guru Mapel'],
            'login_route' => 'guru-mapel.login',
        ],
        'gpk' => [
            'page_title' => 'Masuk Guru Pendamping Khusus',
            'form_title' => 'Selamat datang, GPK',
            'description' => 'Masuk menggunakan akun Google GPK yang telah didaftarkan oleh Super Admin.',
            'label' => 'Guru Pendamping Khusus (GPK)',
            'database_roles' => ['GPK'],
            'login_route' => 'auth.role.gpk.login',
        ],
        'tim-inklusi' => [
            'page_title' => 'Masuk Tim Inklusi',
            'form_title' => 'Selamat datang, Tim Inklusi',
            'description' => 'Masuk menggunakan akun Google Tim Inklusi yang telah didaftarkan oleh Super Admin.',
            'label' => 'Tim Inklusi',
            'database_roles' => ['Tim Inklusi'],
            'login_route' => 'auth.role.tim-inklusi.login',
        ],
        'admin' => [
            'page_title' => 'Masuk Super Admin',
            'form_title' => 'Selamat datang, Super Admin',
            'description' => 'Masuk menggunakan akun Google Super Admin yang telah terdaftar.',
            'label' => 'Super Admin',
            'database_roles' => ['Admin', 'Admin / Pengelola'],
            'login_route' => 'auth.role.admin.login',
        ],
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
        if (Auth::guard('staff')->check()) {
            return $this->redirectFor(Auth::guard('staff')->user()->loadMissing('role'));
        }

        return view('auth.login', $this->loginViewData());
    }

    public function roleLogin(Request $request, string $role): View|RedirectResponse
    {
        $context = $this->roleContext($role);
        $account = Auth::guard('staff')->user()?->loadMissing('role');

        if ($account && $this->accountMatchesContext($account, $role)) {
            return $this->redirectFor($account);
        }

        return view('auth.login', $this->loginViewData(
            $role,
            $account ? 'Anda masih masuk dengan role lain. Keluar terlebih dahulu sebelum menggunakan halaman login ini.' : null,
        ));
    }

    public function redirectWithoutRole(): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'google' => 'Pilih halaman login sesuai role sebelum melanjutkan dengan Google.',
        ]);
    }

    public function redirect(Request $request, string $role): RedirectResponse
    {
        $context = $this->roleContext($role);

        if (! $this->googleConfigured()) {
            return redirect()->route($context['login_route'])->withErrors([
                'google' => 'Google Login belum dikonfigurasi oleh pengelola sistem.',
            ]);
        }

        $account = Auth::guard('staff')->user()?->loadMissing('role');
        if ($account) {
            if ($this->accountMatchesContext($account, $role)) {
                return $this->redirectFor($account);
            }

            return redirect()->route($context['login_route'])->withErrors([
                'google' => 'Keluar dari akun yang sedang aktif sebelum masuk menggunakan role lain.',
            ]);
        }

        $request->session()->put(self::LOGIN_CONTEXT_SESSION, $role);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $role = $request->session()->pull(self::LOGIN_CONTEXT_SESSION);
        $context = is_string($role) && array_key_exists($role, self::ROLE_CONTEXTS)
            ? self::ROLE_CONTEXTS[$role]
            : null;

        if (! $context) {
            return redirect()->route('login')->withErrors([
                'google' => 'Konteks login tidak valid atau sudah kedaluwarsa. Pilih kembali halaman login sesuai role.',
            ]);
        }

        if (! $this->googleConfigured()) {
            return redirect()->route($context['login_route'])->withErrors([
                'google' => 'Google Login belum dikonfigurasi oleh pengelola sistem.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $account = $this->resolveAccount($googleUser, $role);
        } catch (ValidationException $exception) {
            return redirect()->route($context['login_route'])->withErrors($exception->errors());
        } catch (InvalidStateException) {
            return redirect()->route($context['login_route'])->withErrors([
                'google' => 'Sesi Google Login tidak valid atau sudah kedaluwarsa. Silakan coba kembali.',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Google Login callback gagal.', [
                'exception' => $exception::class,
            ]);

            return redirect()->route($context['login_route'])->withErrors([
                'google' => 'Google Login tidak dapat diproses saat ini. Silakan coba kembali.',
            ]);
        }

        Auth::guard('staff')->login($account);
        if ($account->role?->nama_role === 'Guru Mapel') {
            Auth::guard('guru_mapel')->login($account);
        } else {
            Auth::guard('guru_mapel')->logout();
        }
        Auth::guard('guru_dev')->logout();
        $request->session()->forget(['dev_role', 'dev_pengguna_id', 'guru_note_creation_token']);
        $request->session()->regenerate();

        return $this->redirectFor($account);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('staff')->logout();
        Auth::guard('guru_mapel')->logout();
        $request->session()->forget('guru_note_creation_token');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function resolveAccount(SocialiteUser $googleUser, string $roleContext): Pengguna
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
            return DB::transaction(function () use ($googleId, $email, $roleContext): Pengguna {
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
                        $this->ensureAllowed($account, $roleContext);
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

                $this->ensureAllowed($account, $roleContext);

                return $account;
            }, 3);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'google' => 'Akun Google baru saja dihubungkan pada sesi lain. Silakan coba kembali atau hubungi Super Admin.',
            ]);
        }
    }

    private function ensureAllowed(Pengguna $account, ?string $roleContext = null): void
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

        if ($roleContext !== null && ! $this->accountMatchesContext($account, $roleContext)) {
            throw ValidationException::withMessages([
                'google' => 'Akun Google tidak memiliki akses untuk halaman login yang dipilih.',
            ]);
        }
    }

    private function redirectFor(Pengguna $account): RedirectResponse
    {
        $production = ! app()->environment(['local', 'testing']);
        $route = match ($account->role?->nama_role) {
            'Admin', 'Admin / Pengelola' => $production ? 'dev.preview' : 'staff.admin.dashboard',
            'GPK' => $production ? 'dev.gpk.dashboard' : 'staff.gpk.dashboard',
            'Tim Inklusi' => $production ? 'dev.tim-inklusi.dashboard' : 'staff.tim-inklusi.dashboard',
            'Guru Mapel' => 'guru-mapel.dashboard',
            'Kepala Sekolah' => 'staff.principal.dashboard',
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

    private function roleContext(string $role): array
    {
        abort_unless(array_key_exists($role, self::ROLE_CONTEXTS), 404);

        return self::ROLE_CONTEXTS[$role];
    }

    private function accountMatchesContext(Pengguna $account, string $role): bool
    {
        $context = $this->roleContext($role);
        $databaseRole = $account->role?->nama_role;

        return $account->status_akun === 'Aktif'
            && in_array($databaseRole, $context['database_roles'], true)
            && ($role !== 'admin' || $account->is_super_admin);
    }

    private function loginViewData(?string $role = null, ?string $loginError = null): array
    {
        $loginContext = $role === null ? null : ['key' => $role] + $this->roleContext($role);
        $roleOptions = collect(self::ROLE_CONTEXTS)->map(fn (array $context, string $key) => [
            'label' => $context['label'],
            'description' => $context['page_title'],
            'url' => route($context['login_route']),
        ])->values()->all();

        return compact('loginContext', 'roleOptions', 'loginError') + [
            'googleConfigured' => $this->googleConfigured(),
        ];
    }
}
