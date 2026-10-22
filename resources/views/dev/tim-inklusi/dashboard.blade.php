<x-layouts.preview :role="$role" :roles="$previewRoles" active="dashboard" title="Dashboard Guru Pendamping">
    <x-preview.topbar title="Dashboard Guru Pendamping" subtitle="Pantau siswa dampingan dan status catatan pendampingan dari satu tempat." :profile="$profile" />
    <div class="abk-breadcrumb">Dashboard</div>
    <x-preview.flash-messages />

    <div class="abk-welcome">Selamat datang, {{ $pendamping->nama }}</div>

    <div class="row g-3">
        <div class="col-12 col-lg-4"><x-preview.stat-card icon="•" tone="indigo" :value="$stats['students']" label="Siswa Dampingan" /></div>
        <div class="col-12 col-lg-4"><x-preview.stat-card icon="•" tone="amber" :value="$stats['pending']" label="Menunggu Verifikasi" /></div>
        <div class="col-12 col-lg-4"><x-preview.stat-card icon="•" tone="teal" :value="$stats['verified']" label="Terverifikasi" /></div>
    </div>

    <h2 class="abk-section-heading">Catatan pendampingan terbaru</h2>
    <p class="abk-section-description">Pantau status catatan pendampingan yang Anda kirim ke GPK.</p>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($latestNotes as $note)
                <tr>
                    <td class="abk-data-name">{{ $note->siswa->nama_siswa }}</td>
                    <td>{{ $note->siswa->kelas?->nama_kelas ?: '—' }}</td>
                    <td>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d M Y') }}</td>
                    <td><x-preview.status-badge :status="$note->status_pengajuan" /></td>
                    <td><a class="abk-button text-decoration-none" href="{{ $note->status_pengajuan === 'Perlu Revisi' ? route('dev.tim-inklusi.notes.edit', $note) : route('dev.tim-inklusi.notes.show', $note) }}">{{ $note->status_pengajuan === 'Perlu Revisi' ? 'Perbaiki Catatan' : ($note->status_pengajuan === 'Draft' ? 'Edit Draft' : 'Lihat Catatan') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada catatan pendampingan.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</x-layouts.preview>
