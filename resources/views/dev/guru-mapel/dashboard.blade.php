<x-layouts.preview :role="$role" :roles="$roles" active="dashboard" title="Dashboard Guru Mata Pelajaran">
    <x-preview.topbar title="Dashboard Guru Mata Pelajaran" subtitle="Pantau siswa yang Anda ajar dan status catatan perkembangan dari satu tempat." :profile="$profile" />
    <x-preview.breadcrumbs />
    <x-preview.flash-messages />

    <div class="abk-welcome">Selamat datang, {{ $profile['name'] }}</div>
    @php($numbers = array_values($stats))
    <div class="row g-3 abk-stat-grid abk-stat-grid-3">
        <div class="col-12 col-lg-4"><x-preview.stat-card icon="&bull;" tone="indigo" :value="$numbers[0]" label="Siswa yang Diajar" /></div>
        <div class="col-12 col-lg-4"><x-preview.stat-card icon="&bull;" tone="amber" :value="$numbers[1]" label="Menunggu Verifikasi" /></div>
        <div class="col-12 col-lg-4"><x-preview.stat-card icon="&bull;" tone="teal" :value="$numbers[2]" label="Terverifikasi" /></div>
    </div>

    <h2 class="abk-section-heading">Catatan perkembangan terbaru</h2>
    <p class="abk-section-description">Pantau status catatan perkembangan yang Anda kirim ke GPK.</p>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($notes as $note)
                <tr><td class="abk-data-name">{{ $note->siswa->nama_siswa }}</td><td>{{ $note->siswa->kelas?->nama_kelas ?: '—' }}</td><td>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d M Y') }}</td><td><x-preview.status-badge :status="$note->status_pengajuan" /></td><td><a class="abk-button text-decoration-none" href="{{ in_array($note->status_pengajuan, ['Draft', 'Perlu Revisi'], true) ? route('dev.guru-mapel.notes.revise', $note) : route('dev.guru-mapel.notes.show', $note) }}">{{ $note->status_pengajuan === 'Draft' ? 'Edit Draft' : ($note->status_pengajuan === 'Perlu Revisi' ? 'Perbaiki Catatan' : 'Lihat Catatan') }}</a></td></tr>
            @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada catatan perkembangan.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</x-layouts.preview>
