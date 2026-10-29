<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" title="Data Siswa Dampingan">
    <x-preview.topbar title="Data Siswa Dampingan" subtitle="Daftar siswa ABK yang menjadi tanggung jawab pendampingan Anda." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.tim-inklusi.dashboard')" :items="[['Data Siswa', null]]" />

    <section class="abk-panel abk-inclusion-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.tim-inklusi.students.index') }}">
            @if ($filters['tingkat'])<input type="hidden" name="tingkat" value="{{ $filters['tingkat'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] }}" placeholder="Cari nama siswa..." aria-label="Cari nama siswa">
        </form>
        <nav class="abk-chip-list" aria-label="Filter tingkat kelas">
            @foreach (['' => 'Semua', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'] as $value => $label)
                <a class="abk-filter {{ $filters['tingkat'] === $value ? 'active' : '' }}" href="{{ route('dev.tim-inklusi.students.index', array_filter(['cari' => $filters['cari'], 'tingkat' => $value])) }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="abk-users-count">Total {{ $students->total() }} siswa terdaftar</div>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-students-table abk-student-directory-table abk-responsive-card-table align-middle">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Jenis Disabilitas</th><th>Guru Pendamping</th><th>Catatan Terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($students as $student)
                <tr>
                    <td class="abk-data-name"><div class="abk-student-table-name"><x-preview.student-avatar :student="$student" size="small" /><span>{{ $student->nama_siswa }}</span></div></td>
                    <td>{{ $student->kelas?->nama_kelas ?: '—' }}</td>
                    <td><span class="abk-need-pill">{{ $student->kebutuhan_khusus ?: 'Belum dicatat' }}</span></td>
                    <td>{{ $pendamping->nama }}</td>
                    <td>{{ $student->catatan_terakhir ? \Illuminate\Support\Carbon::parse($student->catatan_terakhir)->locale('id')->translatedFormat('d M Y') : '—' }}</td>
                    <td><div class="d-grid gap-1">
                        <a class="abk-button text-decoration-none" href="{{ route('dev.tim-inklusi.students.show', $student) }}">Lihat Profil</a>
                        <a class="abk-dark-button" href="{{ route('dev.tim-inklusi.notes.create', $student) }}">Tambah Catatan</a>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data yang cocok. Coba pilihan lain atau tampilkan semua data.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$students" />
</x-layouts.preview>
