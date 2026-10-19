<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Orang Tua — SMART-ABK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-login">
    <main class="abk-login-card">
        <div class="abk-login-brand">INKLUSI</div>
        <p class="abk-subtitle mt-2 mb-0">Masuk sebagai Orang Tua/Wali</p>
        <div class="abk-login-note">Gunakan username dan password yang diberikan oleh admin sekolah.</div>
        <form method="POST" action="{{ route('dev.parent.login.store') }}">
            @csrf
            <label class="abk-login-label" for="username">Username</label>
            <input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <label class="abk-login-label mt-3" for="password">Password</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="current-password" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button class="abk-login-submit" type="submit">Masuk</button>
        </form>
        <a class="d-inline-block mt-3 small" href="{{ route('dev.login') }}">Kembali ke pemilih role development</a>
    </main>
</body>
</html>
