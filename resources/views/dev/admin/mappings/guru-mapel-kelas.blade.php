<x-layouts.preview :role="$role" :roles="$previewRoles" active="mappings" title="Penugasan Guru">
    <x-preview.topbar title="Penugasan Guru" subtitle="Tinjau hubungan Guru Mata Pelajaran dengan kelas dan mata pelajaran yang diampu." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> Hubungan Data <span>/</span> Penugasan Guru</div>

    @if (session('success'))<div class="alert alert-success abk-alert">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger abk-alert">{{ session('error') }}</div>@endif

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.mappings.guru-mapel-kelas.index') }}">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari guru, mata pelajaran, atau kelas...">
        </form>
        <div class="abk-users-count">Total {{ $mappings->total() }} pemetaan</div>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table abk-table align-middle mb-0">
                <thead><tr><th>Guru</th><th>Mata Pelajaran</th><th>Kelas</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($mappings as $mapping)
                        <tr>
                            <td><div class="abk-data-name">{{ $mapping->guru?->nama }}</div><div class="small text-muted">{{ $mapping->guru?->role?->nama_role }}</div></td>
                            <td>{{ $mapping->mataPelajaran?->nama_mapel }}</td>
                            <td>{{ $mapping->kelas?->nama_kelas }}<div class="small text-muted">{{ $mapping->kelas?->tingkat }} · {{ $mapping->kelas?->tahun_ajaran }}</div></td>
                            <td>
                                <form method="POST" action="{{ route('dev.admin.mappings.guru-mapel-kelas.destroy', $mapping) }}" data-confirm-form data-confirm-title="Lepaskan pemetaan guru?" data-confirm-message="Hubungan guru, kelas, dan mata pelajaran ini akan dilepaskan. Riwayat lain tetap tersimpan." data-confirm-subject="{{ $mapping->guru?->nama }} — {{ $mapping->mataPelajaran?->nama_mapel }} — {{ $mapping->kelas?->nama_kelas }}" data-confirm-button="Lepaskan Pemetaan" data-confirm-variant="destructive">
                                    @csrf
                                    @method('DELETE')
                                    <button class="abk-button abk-button-danger" type="submit">Lepaskan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada pemetaan guru, kelas, dan mata pelajaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <x-preview.pagination :paginator="$mappings" />
</x-layouts.preview>
