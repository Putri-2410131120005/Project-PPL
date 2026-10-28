<x-layouts.preview :role="$role" :roles="$roles" active="history" title="Riwayat Catatan">
    <x-preview.topbar title="Riwayat Catatan Guru Mata Pelajaran" subtitle="Pantau catatan perkembangan yang sudah dibuat dan status verifikasinya." :profile="$profile" />
    <x-preview.breadcrumbs :items="[['Riwayat Catatan', null]]" />

    <section class="abk-panel abk-inclusion-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.guru-mapel.notes.status') }}">
            @if (request('status', 'Semua') !== 'Semua')<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input class="form-control abk-search-input" name="q" value="{{ request('q') }}" placeholder="Cari nama siswa..." aria-label="Cari nama siswa">
        </form>
        <nav class="abk-chip-list" aria-label="Filter status catatan">
            @foreach (['Semua' => 'Semua'] + $labels as $value => $label)
                <a class="abk-filter {{ request('status', 'Semua') === $value ? 'active' : '' }}" href="{{ route('dev.guru-mapel.notes.status', array_filter(['q' => request('q'), 'status' => $value === 'Semua' ? null : $value])) }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="abk-users-count">Total {{ $notes->total() }} catatan</div>
    </section>

    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle">
        <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($notes as $note)
            <tr><td class="abk-data-name">{{ $note->siswa->nama_siswa }}</td><td>{{ $note->siswa->kelas?->nama_kelas ?: '—' }}</td><td>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d M Y') }}</td><td><x-preview.status-badge :status="$note->status_pengajuan" /></td><td><a class="abk-button text-decoration-none" href="{{ in_array($note->status_pengajuan, ['Draft', 'Perlu Revisi'], true) ? route('dev.guru-mapel.notes.revise', $note) : route('dev.guru-mapel.notes.show', $note) }}">{{ $note->status_pengajuan === 'Draft' ? 'Edit Draft' : ($note->status_pengajuan === 'Perlu Revisi' ? 'Perbaiki Catatan' : 'Lihat Catatan') }}</a></td></tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada catatan yang cocok dengan filter.</td></tr>
        @endforelse
        </tbody>
    </table></div></section>
    <x-preview.pagination :paginator="$notes" />
</x-layouts.preview>
