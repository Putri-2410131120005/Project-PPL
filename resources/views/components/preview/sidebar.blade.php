@props(['role', 'roles'])
@php
    $menus = match ($role) {
        'admin' => ['Dashboard', 'Pengguna', 'Siswa ABK', 'Kelas & Mapel', 'Pemetaan Data'],
        'guru-mapel' => ['Dashboard', 'Data Siswa', 'Status Verifikasi', 'Riwayat Perkembangan'],
        default => ['Dashboard'],
    };
@endphp
<aside class="abk-sidebar">
    <div class="abk-brand">INKLUSI</div>
    <div class="abk-brand-subtitle">Monitoring ABK</div>
    <nav class="abk-nav d-flex flex-column">
        @foreach ($menus as $index => $menu)
            <a class="abk-nav-link {{ $index === 0 ? 'active' : '' }}" href="{{ route('dev.preview', $role) }}">{{ $menu }}</a>
        @endforeach
    </nav>
    <div class="mt-4 pt-3 border-top border-secondary-subtle abk-dev-controls">
        <div class="small text-uppercase text-white-50 mb-2">Development only</div>
        <div class="small text-white-50 mb-3">Role aktif: {{ $roles[$role]['label'] }}</div>
        <form method="POST" action="{{ route('dev.change-role') }}">
            @csrf
            <button type="submit" class="abk-dev-button">Ganti Role</button>
        </form>
        <form method="POST" action="{{ route('dev.logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="abk-dev-button abk-dev-button-muted">Keluar</button>
        </form>
    </div>
    <div class="abk-sidebar-footer">{{ $roles[$role]['label'] }}<div class="abk-preview-only">Preview lokal</div></div>
</aside>
