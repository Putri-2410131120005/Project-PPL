<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password — SMART-ABK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-login">
    <main class="abk-login-card">
        <div class="abk-login-brand">INKLUSI</div>
        <p class="abk-subtitle mt-2 mb-0">Buat password pribadi</p>
        <div class="abk-login-note">Untuk keamanan akun {{ $parent->nama }}, password sementara harus diganti sebelum membuka dashboard.</div>
        <form method="POST" action="{{ route('dev.parent.password.update') }}">
            @csrf
            @method('PUT')
            <label class="abk-login-label" for="password">Password baru</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <label class="abk-login-label mt-3" for="password_confirmation">Ulangi password baru</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            <button class="abk-login-submit" type="submit">Simpan Password</button>
        </form>
    </main>
</body>
</html>
