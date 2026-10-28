<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" title="Data Siswa ABK">
    <x-preview.topbar title="Data Siswa ABK" subtitle="Daftar siswa ABK yang menjadi tanggung jawab GPK." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span>Data Siswa</div>
    <section class="abk-panel abk-inclusion-toolbar">
        <form class="abk-search-form" method="GET">
            @if ($filters['tingkat'])<input type="hidden" name="tingkat" value="{{ $filters['tingkat'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] }}" placeholder="Cari nama atau NIS siswa...">
        </form>
        <nav class="abk-chip-list" aria-label="Filter tingkat kelas">
            @foreach (['' => 'Semua', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'] as $value => $label)
                <a class="abk-filter {{ $filters['tingkat'] === $value ? 'active' : '' }}" href="{{ route('dev.gpk.students.index', array_filter(['cari' => $filters['cari'], 'tingkat' => $value])) }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="abk-users-count">Total {{ $students->total() }} siswa terdaftar</div>
    </section>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle">
            <thead><tr><th>Nama Siswa</th><th>NIS</th><th>Kelas</th><th>Jenis Disabilitas</th><th>Guru Pendamping</th><th>Catatan Terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($students as $student)
                @php
                    $companions = $student->relasiPendampingSiswa->filter(fn ($relasi) => $relasi->pendamping?->role?->nama_role === 'Tim Inklusi')->pluck('pendamping.nama')->filter()->join(', ');
                    $lastDate = collect([$student->perkembangan_terakhir, $student->pendampingan_terakhir])->filter()->sortDesc()->first();
                @endphp
                <tr>
                    <td class="abk-data-name">{{ $student->nama_siswa }}</td><td>{{ $student->nis }}</td>
                    <td>{{ $student->kelas?->nama_kelas ?: '—' }}</td><td><span class="abk-need-pill">{{ $student->kebutuhan_khusus ?: 'Belum dicatat' }}</span></td>
                    <td>{{ $companions ?: '—' }}</td>
                    <td>{{ $lastDate ? \Illuminate\Support\Carbon::parse($lastDate)->locale('id')->translatedFormat('d M Y') : '—' }}</td>
                    <td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.students.show', $student) }}">Lihat Profil</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="abk-empty-cell">Tidak ada data yang cocok dengan pencarian atau filter.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$students" />
</x-layouts.preview>
