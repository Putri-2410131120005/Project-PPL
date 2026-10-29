<x-layouts.preview :role="$role" :roles="$roles" active="students" title="Detail Siswa">
    <x-preview.topbar title="Detail Siswa" subtitle="Profil ringkas dan riwayat perkembangan sebagai referensi pembelajaran." :profile="$profile" />
    <x-preview.breadcrumbs :items="[['Data Siswa', route('dev.guru-mapel.students.index')], [$student->nama_siswa, null]]" />

    @php($companions = $student->relasiPendampingSiswa->map->pendamping->filter(fn ($person) => $person && $person->status_akun === 'Aktif' && in_array($person->role?->nama_role, ['Tim Inklusi', 'GPK'], true))->pluck('nama')->join(', '))
    <section class="abk-panel abk-student-profile">
        <div class="abk-student-identity"><x-preview.student-avatar :student="$student" /><div><h2>{{ $student->nama_siswa }}</h2><p>{{ $student->kelas?->nama_kelas ?: 'Tanpa kelas' }} <span>&bull;</span> NIS {{ $student->nis }}</p><span class="abk-need-pill">{{ $student->kebutuhan_khusus ?: 'Kebutuhan belum dicatat' }}</span></div></div>
        <div class="abk-profile-actions"><small>Guru Pendamping</small><strong>{{ $companions ?: 'Belum tersedia' }}</strong><small class="mt-2">Aksi</small><a class="abk-dark-button" href="{{ route('dev.guru-mapel.notes.create', $student) }}">Tambah Catatan Perkembangan</a><a class="abk-dark-button" href="{{ route('dev.guru-mapel.recommendations.show', $student) }}">Lihat Rekomendasi</a></div>
    </section>

    <h2 class="abk-section-heading">Riwayat perkembangan</h2>
    <p class="abk-section-description">Catatan yang dipublikasikan GPK sebagai referensi pembelajaran.</p>
    <section class="abk-panel abk-inclusion-toolbar mb-3">
        <strong>Sumber catatan</strong>
        <nav class="abk-chip-list" aria-label="Filter sumber riwayat perkembangan">
            @foreach (['' => 'Semua', 'perkembangan' => 'Guru Mata Pelajaran', 'pendampingan' => 'Tim Inklusi'] as $value => $label)
                <a class="abk-filter {{ $historySource === $value ? 'active' : '' }}" href="{{ route('dev.guru-mapel.students.show', array_filter(['siswa' => $student->getKey(), 'sumber' => $value])) }}">{{ $label }}</a>
            @endforeach
        </nav>
    </section>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle">
            <thead><tr><th>Tanggal</th><th>Sumber</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse ($history as $item)
                <tr><td>{{ $item['note']->tanggal_catatan->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $item['source'] === 'perkembangan' ? 'Guru Mata Pelajaran' : 'Tim Inklusi' }}</td><td><x-preview.status-badge status="Terverifikasi" /></td><td><a class="abk-button text-decoration-none" href="{{ route('dev.guru-mapel.references.show', ['source' => $item['source'], 'id' => $item['note']->getKey()]) }}">Lihat Catatan</a></td></tr>
            @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat yang dapat ditampilkan.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</x-layouts.preview>
