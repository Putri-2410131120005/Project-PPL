<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — SMART-ABK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="abk-login">
    <main class="abk-login-card">
        <div class="abk-login-brand">INKLUSI</div>
        <p class="abk-subtitle mt-2 mb-0">SMART-ABK</p>
        <div class="abk-login-note">Pilih peran untuk membuka area kerja SMART-ABK.</div>

        <form method="POST" action="{{ route('dev.login.store') }}">
            @csrf
            <label class="abk-login-label" for="role">Masuk sebagai</label>
            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                @foreach ($roles as $key => $profile)
                    <option value="{{ $key }}" @selected(old('role', 'admin') === $key)>{{ $profile['label'] }}</option>
                @endforeach
            </select>
            @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if ($timInklusiAccounts->isNotEmpty())
                <div class="mt-3" data-tim-inklusi-account>
                    <label class="abk-login-label" for="pendamping_id">Akun Tim Inklusi (mode development)</label>
                    <select class="form-select" id="pendamping_id" name="pendamping_id">
                        @foreach ($timInklusiAccounts as $account)
                            <option value="{{ $account->getKey() }}" @selected((string) old('pendamping_id') === (string) $account->getKey())>{{ $account->nama }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Digunakan hanya saat peran Tim Inklusi dipilih.</small>
                </div>
            @endif
            <button class="abk-login-submit" type="submit">Masuk</button>
        </form>
        <a class="d-inline-block mt-3 small" href="{{ route('dev.parent.login') }}">Masuk dengan akun Orang Tua/Wali</a>
    </main>
</body>
</html>
