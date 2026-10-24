<x-layouts.preview :role="$role" :roles="$previewRoles" active="dashboard" title="Dashboard GPK">
    <x-preview.topbar title="Dashboard GPK" subtitle="Pantau verifikasi catatan, perkembangan siswa, rekap, dan laporan dari satu tempat." :profile="$profile" />
    <div class="abk-breadcrumb">Dashboard</div>
    <x-preview.flash-messages />
    <div class="abk-welcome">Selamat datang, {{ $gpk->nama }}</div>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card icon="•" tone="indigo" :value="$stats['students']" label="Siswa ABK" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card icon="•" tone="amber" :value="$stats['pending']" label="Menunggu Verifikasi" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card icon="•" tone="red" :value="$stats['revision']" label="Perlu Revisi" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card icon="•" tone="teal" :value="$stats['verified']" label="Terverifikasi" /></div>
    </div>

    <h2 class="abk-section-heading">Catatan menunggu verifikasi</h2>
    <p class="abk-section-description">Catatan terbaru dari Guru Mata Pelajaran dan Tim Inklusi yang belum ditinjau GPK.</p>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Tanggal</th><th>Sumber Catatan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($pendingNotes as $item)
                <tr>
                    <td class="abk-data-name">{{ $item['student']->nama_siswa }}</td>
                    <td>{{ $item['student']->kelas?->nama_kelas ?: '—' }}</td>
                    <td>{{ $item['date']->locale('id')->translatedFormat('d M Y') }}</td>
                    <td>{{ $item['source'] }}</td>
                    <td><x-preview.status-badge status="Dikirim" /></td>
                    <td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.notes.show', ['type' => $item['type'], 'id' => $item['note']->getKey()]) }}">Tinjau</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="abk-empty-cell">Tidak ada catatan yang menunggu verifikasi.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</x-layouts.preview>
