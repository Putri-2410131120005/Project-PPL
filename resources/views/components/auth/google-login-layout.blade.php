@props(['googleConfigured' => false])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — SMART-ABK</title>
    <x-pwa.meta />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-parent-access-page">
    <main class="abk-parent-access-main">
        <h1>Masuk SMART-ABK</h1>
        <div class="abk-pwa-login-actions">
            <button class="abk-pwa-button" type="button" data-pwa-install hidden>Instal aplikasi</button>
            <span class="abk-pwa-hint" data-pwa-install-hint hidden></span>
        </div>
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
                <p>Satu pintu masuk untuk orang tua, guru, dan pengelola sekolah. Sistem akan mengarahkan Anda ke halaman sesuai hak akses akun.</p>
            </div>

            <div class="abk-parent-access-form-wrap">
                <section class="abk-parent-access-form-card">
                    <h2>Selamat datang</h2>
                    <p>Orang Tua dan Super Admin dapat masuk dengan username serta password. Akun internal lainnya menggunakan Google.</p>

                    <form method="POST" action="{{ route('dev.parent.login.store') }}">
                        @csrf
                        <div class="abk-parent-access-field">
                            <label for="username">Username</label>
                            <div>
                                <span aria-hidden="true">@</span>
                                <input class="@error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" autocomplete="username" placeholder="Masukkan username" required autofocus>
                            </div>
                            @error('username')<small class="abk-parent-access-error">{{ $message }}</small>@enderror
                        </div>

                        <div class="abk-parent-access-field">
                            <label for="password">Password</label>
                            <div>
                                <span aria-hidden="true">●</span>
                                <input type="password" class="@error('password') is-invalid @enderror" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required>
                                <button type="button" class="abk-password-toggle" data-password-toggle aria-controls="password" aria-pressed="false">
                                    <span data-password-toggle-label>Lihat</span>
                                </button>
                            </div>
                            @error('password')<small class="abk-parent-access-error">{{ $message }}</small>@enderror
                        </div>

                        <button type="submit">Masuk</button>
                    </form>

                    <div class="abk-login-divider"><span>atau</span></div>

                    @error('google')<div class="abk-google-login-error">{{ $message }}</div>@enderror
                    <a class="abk-google-login-button {{ $googleConfigured ? '' : 'disabled' }}"
                       href="{{ $googleConfigured ? route('auth.google.redirect') : '#' }}"
                       @unless($googleConfigured) aria-disabled="true" @endunless>
                        <svg class="abk-google-logo" aria-hidden="true" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.253-.164-1.841H9v3.482h4.844a4.14 4.14 0 0 1-1.797 2.716v2.258h2.908c1.703-1.567 2.685-3.874 2.685-6.615Z"/>
                            <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.955-2.18l-2.908-2.258c-.806.54-1.836.859-3.047.859-2.344 0-4.328-1.584-5.037-3.71H.957v2.332A9 9 0 0 0 9 18Z"/>
                            <path fill="#FBBC05" d="M3.963 10.711A5.41 5.41 0 0 1 3.682 9c0-.594.102-1.172.281-1.711V4.957H.957A9 9 0 0 0 0 9c0 1.452.347 2.827.957 4.043l3.006-2.332Z"/>
                            <path fill="#EA4335" d="M9 3.58c1.322 0 2.508.455 3.441 1.346l2.581-2.581C13.464.891 11.426 0 9 0A9 9 0 0 0 .957 4.957l3.006 2.332C4.672 5.163 6.656 3.58 9 3.58Z"/>
                        </svg>
                        Masuk dengan Google
                    </a>

                    <div class="abk-parent-access-help">
                        Google digunakan oleh Admin/Pengelola, GPK, Tim Inklusi, Kepala Sekolah, Guru Mata Pelajaran, dan Super Admin.
                        @unless($googleConfigured)<br><span class="text-danger">Google Login belum dikonfigurasi.</span>@endunless
                    </div>
                </section>
            </div>
        </section>
    </main>
</body>
</html>
