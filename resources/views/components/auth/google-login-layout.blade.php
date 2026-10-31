@props([
    'loginContext' => null,
    'roleOptions' => [],
    'googleConfigured' => false,
    'loginError' => null,
])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $loginContext['page_title'] ?? 'Masuk SMART-ABK' }} — SMART-ABK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-parent-access-page">
    <main class="abk-parent-access-main">
        <h1>{{ $loginContext['page_title'] ?? 'Masuk SMART-ABK' }}</h1>
        <section class="abk-parent-access-frame">
            <div class="abk-parent-access-visual">
                <span class="abk-parent-access-glow abk-parent-access-glow-one" aria-hidden="true"></span>
                <span class="abk-parent-access-glow abk-parent-access-glow-two" aria-hidden="true"></span>
                <div class="abk-parent-access-grid" aria-hidden="true"></div>
                <div class="abk-parent-access-brand">
                    <span class="abk-parent-access-mark">SA</span>
                    <strong>SMART-ABK</strong>
                    <small>Sistem Monitoring Anak Berkebutuhan Khusus</small>
                </div>
                <p>Akses ruang kerja sekolah dengan identitas yang aman dan akun yang telah didaftarkan Super Admin.</p>
            </div>
            <div class="abk-parent-access-form-wrap">
                <section class="abk-parent-access-form-card">
                    <h2>{{ $loginContext['form_title'] ?? 'Pilih akses staf' }}</h2>
                    <p>{{ $loginContext['description'] ?? 'Pilih halaman login sesuai role yang telah ditetapkan Super Admin.' }}</p>

                    @if ($loginError)
                        <div class="alert alert-warning small">{{ $loginError }}</div>
                    @endif
                    @error('google')<div class="alert alert-danger small">{{ $message }}</div>@enderror

                    @if ($loginContext)
                        <a class="abk-google-login-button {{ $googleConfigured && ! $loginError ? '' : 'disabled' }}"
                           href="{{ $googleConfigured && ! $loginError ? route('auth.google.role.redirect', $loginContext['key']) : '#' }}"
                           @unless($googleConfigured && ! $loginError) aria-disabled="true" @endunless>
                            <svg class="abk-google-logo" aria-hidden="true" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.253-.164-1.841H9v3.482h4.844a4.14 4.14 0 0 1-1.797 2.716v2.258h2.908c1.703-1.567 2.685-3.874 2.685-6.615Z"/>
                                <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.955-2.18l-2.908-2.258c-.806.54-1.836.859-3.047.859-2.344 0-4.328-1.584-5.037-3.71H.957v2.332A9 9 0 0 0 9 18Z"/>
                                <path fill="#FBBC05" d="M3.963 10.711A5.41 5.41 0 0 1 3.682 9c0-.594.102-1.172.281-1.711V4.957H.957A9 9 0 0 0 0 9c0 1.452.347 2.827.957 4.043l3.006-2.332Z"/>
                                <path fill="#EA4335" d="M9 3.58c1.322 0 2.508.455 3.441 1.346l2.581-2.581C13.464.891 11.426 0 9 0A9 9 0 0 0 .957 4.957l3.006 2.332C4.672 5.163 6.656 3.58 9 3.58Z"/>
                            </svg>
                            Masuk dengan Google
                        </a>
                        <div class="abk-parent-access-help">
                            <strong>{{ $loginContext['label'] }}</strong><br>
                            Hak akses tetap diverifikasi dari akun yang terdaftar di SMART-ABK.
                            @unless($googleConfigured)<br><span class="text-danger">Google Login belum dikonfigurasi.</span>@endunless
                        </div>
                        <a class="abk-role-login-back" href="{{ route('login') }}">Pilih role lain</a>
                    @else
                        <nav class="abk-login-role-list" aria-label="Pilihan login staf">
                            @foreach ($roleOptions as $option)
                                <a href="{{ $option['url'] }}">
                                    <strong>{{ $option['label'] }}</strong>
                                    <span>{{ $option['description'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    @endif

                    @if (Route::has('dev.parent.login'))
                        <a class="abk-parent-login-link" href="{{ route('dev.parent.login') }}">Masuk sebagai Orang Tua/Wali</a>
                    @endif
                </section>
            </div>
        </section>
        @if (Route::has('dev.login'))
            <a class="abk-parent-access-back" href="{{ route('dev.login') }}">Login development</a>
        @endif
    </main>
</body>
</html>
