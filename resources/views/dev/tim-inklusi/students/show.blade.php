<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" title="Detail Siswa">
    <x-preview.topbar title="Detail Siswa" subtitle="Profil ringkas dan riwayat perkembangan untuk kebutuhan pendampingan." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.tim-inklusi.dashboard') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.tim-inklusi.students.index') }}">Data Siswa</a> <span>/</span> Detail Siswa</div>

    <section class="abk-panel abk-student-profile">
        <div class="abk-student-identity">
            <x-preview.student-avatar :student="$siswa" />
            <div><h2>{{ $siswa->nama_siswa }}</h2><p>{{ $siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }} <span>•</span> NIS {{ $siswa->nis }}</p><span class="abk-need-pill">{{ $siswa->kebutuhan_khusus ?: 'Kebutuhan belum dicatat' }}</span></div>
        </div>
        <div class="abk-profile-actions"><small>Guru Pendamping</small><strong>{{ $pendamping->nama }}</strong><small class="mt-2">Aksi</small><a class="abk-dark-button" href="{{ route('dev.tim-inklusi.notes.create', $siswa) }}">Tambah Catatan Pendamping</a><a class="abk-dark-button" href="{{ route('dev.tim-inklusi.students.recommendation', $siswa) }}">Lihat Rekomendasi</a></div>
    </section>

    <h2 class="abk-section-heading">Riwayat perkembangan</h2>
    <p class="abk-section-description">Catatan perkembangan dari guru mapel dan pendamping sebagai dasar tindak lanjut.</p>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle">
            <thead><tr><th>Tanggal</th><th>Sumber</th><th>Ringkasan Peristiwa</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($history as $item)
                @php($note = $item['note'])
                <tr>
                    <td>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d M Y') }}</td>
                    <td>{{ $item['type'] === 'perkembangan' ? 'Guru Mata Pelajaran'.($note->mataPelajaran ? ' • '.$note->mataPelajaran->nama_mapel : '') : 'Tim Inklusi / Pendamping' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($item['type'] === 'perkembangan' ? $note->materi_kegiatan : $note->kegiatan_pendampingan, 100) }}</td>
                    <td><x-preview.status-badge :status="$note->status_pengajuan" /></td>
                    <td><a class="abk-button text-decoration-none" href="{{ $item['type'] === 'perkembangan' ? route('dev.tim-inklusi.development-notes.show', [$siswa, $note]) : route('dev.tim-inklusi.notes.show', $note) }}">Lihat Catatan</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat perkembangan yang dapat ditampilkan.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$history" />
</x-layouts.preview>
