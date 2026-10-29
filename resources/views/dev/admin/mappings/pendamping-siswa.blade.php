<x-layouts.preview :role="$role" :roles="$previewRoles" active="mappings" title="Hubungan Pendamping dan Siswa">
    <x-preview.topbar title="Hubungan Pendamping dan Siswa" subtitle="Tinjau GPK atau Tim Inklusi yang mendampingi setiap siswa ABK." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', 'admin')" :items="[['Hubungan Data', null], ['Pendamping Siswa', null]]" />

    <x-preview.flash-messages />

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.mappings.pendamping-siswa.index') }}">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama GPK, NIP, nama siswa, atau NIS...">
        </form>
        <div class="abk-users-count">Total {{ $mappings->total() }} pemetaan</div>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table abk-table align-middle mb-0">
                <thead><tr><th>Guru Pendamping</th><th>Siswa ABK</th><th>Kelas</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($mappings as $mapping)
                        <tr>
                            <td><div class="abk-data-name">{{ $mapping->pendamping?->nama }}</div><div class="small text-muted">NIP {{ $mapping->pendamping?->nip ?: '—' }}</div></td>
                            <td><div class="abk-data-name">{{ $mapping->siswa?->nama_siswa }}</div><div class="small text-muted">NIS {{ $mapping->siswa?->nis }}</div></td>
                            <td>{{ $mapping->siswa?->kelas?->nama_kelas ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('dev.admin.mappings.pendamping-siswa.destroy', $mapping) }}" data-confirm-form data-confirm-title="Lepaskan pemetaan guru pendamping?" data-confirm-message="Guru ini tidak lagi tercatat sebagai pendamping siswa. Riwayat catatan yang sudah dibuat tetap tersimpan." data-confirm-subject="{{ $mapping->pendamping?->nama }} — {{ $mapping->siswa?->nama_siswa }}" data-confirm-button="Lepaskan Pemetaan" data-confirm-variant="destructive">
                                    @csrf
                                    @method('DELETE')
                                    <button class="abk-button abk-button-danger" type="submit">Lepaskan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada pemetaan siswa dengan guru pendamping.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <x-preview.pagination :paginator="$mappings" />
</x-layouts.preview>
