@props(['role', 'roles', 'active' => 'dashboard'])
@php
    $menus = match ($role) {
        'admin' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.preview', 'admin')],
            ['key' => 'users', 'label' => 'Kelola Pengguna', 'href' => route('dev.admin.users.index')],
            ['key' => 'students', 'label' => 'Siswa ABK', 'href' => route('dev.admin.students.index')],
            ['key' => 'classes-mapel', 'label' => 'Kelas & Mata Pelajaran', 'children' => [
                ['label' => 'Master Kelas', 'href' => route('dev.admin.classes.index'), 'active' => request()->routeIs('dev.admin.classes.*')],
                ['label' => 'Master Mata Pelajaran', 'href' => route('dev.admin.subjects.index'), 'active' => request()->routeIs('dev.admin.subjects.*')],
            ]],
            ['key' => 'mappings', 'label' => 'Hubungan Data', 'children' => [
                ['label' => 'Penugasan Guru', 'href' => route('dev.admin.mappings.guru-mapel-kelas.index'), 'active' => request()->routeIs('dev.admin.mappings.guru-mapel-kelas.*')],
                ['label' => 'Pendamping Siswa', 'href' => route('dev.admin.mappings.pendamping-siswa.index'), 'active' => request()->routeIs('dev.admin.mappings.pendamping-siswa.*')],
                ['label' => 'Orang Tua Siswa', 'href' => route('dev.admin.mappings.orang-tua-siswa.index'), 'active' => request()->routeIs('dev.admin.mappings.orang-tua-siswa.*')],
            ]],
        ],
        'guru-mapel' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.preview', $role)],
            ['key' => 'students', 'label' => 'Data Siswa', 'href' => route('dev.preview', $role)],
            ['key' => 'verification', 'label' => 'Status Verifikasi', 'href' => route('dev.preview', $role)],
            ['key' => 'history', 'label' => 'Riwayat Perkembangan', 'href' => route('dev.preview', $role)],
        ],
        'tim-inklusi' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.tim-inklusi.dashboard')],
            ['key' => 'students', 'label' => 'Data Siswa', 'href' => route('dev.tim-inklusi.students.index')],
            ['key' => 'history', 'label' => 'Riwayat Catatan', 'href' => route('dev.tim-inklusi.notes.index')],
        ],
        default => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.preview', $role)],
        ],
    };
@endphp

<aside class="abk-sidebar">
    <div class="abk-brand">INKLUSI</div>
    <div class="abk-brand-subtitle">Monitoring ABK</div>
    <nav class="abk-nav d-flex flex-column" aria-label="Navigasi utama">
        @foreach ($menus as $menu)
            @if (isset($menu['children']))
                <details class="abk-nav-group" @if ($active === $menu['key']) open @endif>
                    <summary class="abk-nav-link {{ $active === $menu['key'] ? 'active' : '' }}">
                        <span>{{ $menu['label'] }}</span><span class="abk-nav-chevron" aria-hidden="true">⌄</span>
                    </summary>
                    <div class="abk-nav-submenu">
                        @foreach ($menu['children'] as $child)
                            <a class="abk-nav-sublink {{ $child['active'] ? 'active' : '' }}" href="{{ $child['href'] }}" @if ($child['active']) aria-current="page" @endif>{{ $child['label'] }}</a>
                        @endforeach
                    </div>
                </details>
            @else
                <a class="abk-nav-link {{ $active === $menu['key'] ? 'active' : '' }}" href="{{ $menu['href'] }}" @if ($active === $menu['key']) aria-current="page" @endif>{{ $menu['label'] }}</a>
            @endif
        @endforeach
    </nav>
    <div class="mt-4 pt-3 border-top border-secondary-subtle abk-session-controls">
        <form method="POST" action="{{ route('dev.change-role') }}" data-confirm-form data-confirm-title="Ganti peran?" data-confirm-message="Sesi saat ini akan diakhiri. Anda dapat memilih peran lain pada halaman berikutnya." data-confirm-button="Ganti Peran">
            @csrf
            <button type="submit" class="abk-dev-button">Ganti Peran</button>
        </form>
        <form method="POST" action="{{ route('dev.logout') }}" class="mt-2" data-confirm-form data-confirm-title="Keluar?" data-confirm-message="Sesi aktif akan diakhiri." data-confirm-button="Keluar" data-confirm-variant="danger">
            @csrf
            <button type="submit" class="abk-dev-button abk-dev-button-muted">Keluar</button>
        </form>
    </div>
    <div class="abk-sidebar-footer">{{ $roles[$role]['label'] }}</div>
</aside>
