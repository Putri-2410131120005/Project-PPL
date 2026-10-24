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
            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" data-development-role>
                @foreach ($roles as $key => $profile)
                    <option value="{{ $key }}" @selected(old('role', 'admin') === $key)>{{ $profile['label'] }}</option>
                @endforeach
            </select>
            @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="mt-3" data-tim-inklusi-account @if (old('role', 'admin') !== 'tim-inklusi') hidden @endif>
                <label class="abk-login-label" for="pendamping_id">Pilih nama Tim Inklusi</label>
                @if ($timInklusiAccounts->isNotEmpty())
                    <select class="form-select" id="pendamping_id" name="pendamping_id">
                        @foreach ($timInklusiAccounts as $account)
                            <option value="{{ $account->getKey() }}" @selected((string) old('pendamping_id') === (string) $account->getKey())>{{ $account->nama }}</option>
                        @endforeach
                    </select>
                    @error('pendamping_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <small class="text-muted">Hanya akun aktif dengan role Tim Inklusi yang dapat masuk.</small>
                @else
                    <div class="alert alert-warning small mb-0">Belum ada akun Tim Inklusi aktif. Tambahkan melalui halaman Admin terlebih dahulu.</div>
                @endif
            </div>
            <div class="mt-3" data-gpk-account @if (old('role', 'admin') !== 'gpk') hidden @endif>
                <label class="abk-login-label" for="gpk_id">Pilih nama GPK</label>
                @if ($gpkAccounts->isNotEmpty())
                    <select class="form-select" id="gpk_id" name="gpk_id">
                        @foreach ($gpkAccounts as $account)
                            <option value="{{ $account->getKey() }}" @selected((string) old('gpk_id') === (string) $account->getKey())>{{ $account->nama }}</option>
                        @endforeach
                    </select>
                    @error('gpk_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <small class="text-muted">Hanya akun aktif dengan role GPK yang dapat masuk.</small>
                @else
                    <div class="alert alert-warning small mb-0">Belum ada akun GPK aktif. Tambahkan melalui halaman Admin terlebih dahulu.</div>
                @endif
            </div>
            <button class="abk-login-submit" type="submit">Masuk</button>
        </form>
        <div class="abk-login-parent-link"><span>Anda orang tua/wali?</span><a href="{{ route('dev.parent.login') }}">Buka login Orang Tua</a></div>
    </main>
</body>
</html>
