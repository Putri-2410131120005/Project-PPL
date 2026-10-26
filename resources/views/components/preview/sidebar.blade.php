@props(['role', 'roles', 'active' => 'dashboard'])
@php
    $menus = match ($role) {
        'admin' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.preview', 'admin')],
            ['key' => 'academic-years', 'label' => 'Tahun Ajaran', 'href' => route('dev.admin.academic-years.index')],
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
            ['key' => 'students', 'label' => 'Data Siswa', 'href' => route('dev.guru-mapel.students.index')],
            ['key' => 'verification', 'label' => 'Status Verifikasi', 'href' => route('dev.guru-mapel.notes.status')],
            ['key' => 'history', 'label' => 'Riwayat Perkembangan', 'href' => route('dev.guru-mapel.history.index')],
        ],
        'tim-inklusi' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.tim-inklusi.dashboard')],
            ['key' => 'students', 'label' => 'Data Siswa', 'href' => route('dev.tim-inklusi.students.index')],
            ['key' => 'history', 'label' => 'Riwayat Catatan', 'href' => route('dev.tim-inklusi.notes.index')],
        ],
        'gpk' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.gpk.dashboard')],
            ['key' => 'students', 'label' => 'Data Siswa', 'href' => route('dev.gpk.students.index')],
            ['key' => 'notes', 'label' => 'Verifikasi Catatan', 'href' => route('dev.gpk.notes.index')],
            ['key' => 'history', 'label' => 'Riwayat Verifikasi', 'href' => route('dev.gpk.history.index')],
            ['key' => 'recaps', 'label' => 'Rekap', 'href' => route('dev.gpk.recaps.index')],
            ['key' => 'reports', 'label' => 'Laporan', 'href' => route('dev.gpk.reports.index')],
            ['key' => 'parents', 'label' => 'Data Orang Tua / Wali', 'href' => route('dev.gpk.parents.index')],
        ],
        'orang-tua' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => route('dev.parent.dashboard')],
            ['key' => 'progress', 'label' => 'Catatan Perkembangan', 'href' => route('dev.parent.notes.index')],
            ['key' => 'reports', 'label' => 'Laporan Perkembangan', 'href' => route('dev.parent.reports.index')],
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
        @if ($role !== 'orang-tua')
        <form method="POST" action="{{ route('dev.change-role') }}" data-confirm-form data-confirm-title="Ganti peran?" data-confirm-message="Sesi saat ini akan diakhiri. Anda dapat memilih peran lain pada halaman berikutnya." data-confirm-button="Ganti Peran">
            @csrf
            <button type="submit" class="abk-dev-button">Ganti Peran</button>
        </form>
        @endif
        <form method="POST" action="{{ route('dev.logout') }}" class="mt-2" data-confirm-form data-confirm-title="Keluar?" data-confirm-message="Sesi aktif akan diakhiri." data-confirm-button="Keluar" data-confirm-variant="danger">
            @csrf
            <button type="submit" class="abk-dev-button abk-dev-button-muted">Keluar</button>
        </form>
    </div>
    <div class="abk-sidebar-footer">{{ $roles[$role]['label'] }}@if (! empty($roles[$role]['secondary']))<small>{{ $roles[$role]['secondary'] }}</small>@endif</div>
</aside>
