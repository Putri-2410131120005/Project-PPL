<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Orang Tua — SMART-ABK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-parent-access-page">
    <main class="abk-parent-access-main">
        <h1>Masuk Orang Tua / Wali</h1>

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
                <p>Pantau perkembangan anak dengan informasi yang aman, terarah, dan terhubung dengan sekolah.</p>
            </div>

            <div class="abk-parent-access-form-wrap">
                <section class="abk-parent-access-form-card">
                    <h2>Selamat datang</h2>
                    <p>Masuk menggunakan akun Orang Tua/Wali.</p>

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
                            </div>
                            @error('password')<small class="abk-parent-access-error">{{ $message }}</small>@enderror
                        </div>

                        <button type="submit">Masuk</button>
                    </form>

                    <div class="abk-parent-access-help">Akun diberikan oleh admin sekolah. Hubungi admin jika mengalami kendala login.</div>
                </section>
            </div>
        </section>

        <a class="abk-parent-access-back" href="{{ route('dev.login') }}">← Kembali ke login staf sekolah</a>
    </main>
</body>
</html>
