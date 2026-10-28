<x-layouts.preview :role="$role" :roles="$roles" active="students" title="Data Siswa yang Diajar">
    <x-preview.topbar title="Data Siswa yang Diajar" subtitle="Daftar siswa pada kelas dan mata pelajaran yang Anda ajar." :profile="$profile" />
    <x-preview.breadcrumbs :items="[['Data Siswa', null]]" />

    <section class="abk-panel abk-inclusion-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.guru-mapel.students.index') }}">
            @if (request('tingkat', 'Semua') !== 'Semua')<input type="hidden" name="tingkat" value="{{ request('tingkat') }}">@endif
            <input class="form-control abk-search-input" name="q" value="{{ request('q') }}" placeholder="Cari nama siswa..." aria-label="Cari nama siswa">
        </form>
        <nav class="abk-chip-list" aria-label="Filter tingkat kelas">
            @foreach (['Semua', 'X', 'XI', 'XII'] as $level)
                <a class="abk-filter {{ request('tingkat', 'Semua') === $level ? 'active' : '' }}" href="{{ route('dev.guru-mapel.students.index', array_filter(['q' => request('q'), 'tingkat' => $level === 'Semua' ? null : $level])) }}">{{ $level }}</a>
            @endforeach
        </nav>
        <div class="abk-users-count">Total {{ $students->total() }} siswa terdaftar</div>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-students-table align-middle">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Jenis Disabilitas</th><th>Guru Pendamping</th><th>Catatan Terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($students as $student)
                @php($companions = $student->relasiPendampingSiswa->map->pendamping->filter(fn ($person) => $person && $person->status_akun === 'Aktif' && in_array($person->role?->nama_role, ['Tim Inklusi', 'GPK'], true))->pluck('nama')->join(', '))
                <tr><td class="abk-data-name"><div class="abk-student-table-name"><x-preview.student-avatar :student="$student" size="small" /><span>{{ $student->nama_siswa }}</span></div></td><td>{{ $student->kelas?->nama_kelas ?: '—' }}</td><td><span class="abk-need-pill">{{ $student->kebutuhan_khusus ?: 'Belum dicatat' }}</span></td><td>{{ $companions ?: 'Belum tersedia' }}</td><td>{{ $student->terakhir ? \Illuminate\Support\Carbon::parse($student->terakhir)->locale('id')->translatedFormat('d M Y') : '—' }}</td><td><div class="d-grid gap-1"><a class="abk-button text-decoration-none" href="{{ route('dev.guru-mapel.students.show', $student) }}">Lihat Profil</a><a class="abk-dark-button" href="{{ route('dev.guru-mapel.notes.create', $student) }}">Tambah Catatan</a></div></td></tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data yang cocok. Coba pilihan lain atau tampilkan semua data.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$students" />
</x-layouts.preview>
