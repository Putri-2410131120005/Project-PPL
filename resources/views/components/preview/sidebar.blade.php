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
    <div class="mt-4 pt-3 border-top border-secondary-subtle">
        <div class="small text-uppercase text-white-50 mb-2">Preview role</div>
        @foreach ($roles as $key => $item)
            <a class="abk-nav-link px-2 py-1 min-vh-0 {{ $key === $role ? 'text-white' : '' }}" href="{{ route('dev.preview', $key) }}">{{ $item['label'] }}</a>
        @endforeach
    </div>
    <div class="abk-sidebar-footer">{{ $roles[$role]['label'] }}<div class="abk-preview-only">Preview lokal</div></div>
</aside>
