<x-layouts.preview :role="$role" :roles="$previewRoles" active="classes-mapel" title="Kelola Mata Pelajaran">
    <x-preview.topbar title="Kelola Mata Pelajaran" subtitle="Atur mata pelajaran aktif dan arsip tanpa menghapus catatan perkembangan." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> Kelas &amp; Mata Pelajaran <span>/</span> Mata Pelajaran</div>

    <x-preview.flash-messages />

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.subjects.index') }}">
            @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari mata pelajaran...">
        </form>
        <div class="abk-users-count">Total {{ $subjects->total() }} mata pelajaran</div>
        <a class="abk-secondary-button" href="{{ route('dev.admin.classes.index') }}">Kelola Kelas</a>
        <a class="abk-primary-button" href="{{ route('dev.admin.subjects.create') }}">Tambah Mata Pelajaran</a>
    </section>

    <section class="abk-panel abk-filter-bar">
        <span>Status:</span>
        <a class="abk-filter {{ empty($filters['status']) ? 'active' : '' }}" href="{{ route('dev.admin.subjects.index', array_filter(['cari' => $filters['cari'] ?? null])) }}">Semua</a>
        @foreach ($subjectStatuses as $status)
            <a class="abk-filter {{ ($filters['status'] ?? '') === $status ? 'active' : '' }}" href="{{ route('dev.admin.subjects.index', array_filter(['cari' => $filters['cari'] ?? null, 'status' => $status])) }}">{{ $status }}</a>
        @endforeach
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-users-table align-middle">
            <thead><tr><th>Mata Pelajaran</th><th>Kelas Terkait</th><th>Status</th><th>Catatan Perkembangan</th><th>Penugasan Guru</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($subjects as $mataPelajaran)
                    @php($willArchive = $mataPelajaran->status_mapel === 'Aktif')
                    <tr>
                        <td class="abk-data-name">{{ $mataPelajaran->nama_mapel }}</td>
                        <td>
                            @forelse ($mataPelajaran->kelas as $class)
                                <span class="abk-compact-tag">{{ $class->tingkat }} — {{ $class->nama_kelas }}</span>
                            @empty
                                <span class="text-muted">Belum ada kelas</span>
                            @endforelse
                        </td>
                        <td><span class="abk-status {{ $willArchive ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $mataPelajaran->status_mapel }}</span></td>
                        <td>{{ $mataPelajaran->catatan_perkembangan_count }}</td>
                        <td>{{ $mataPelajaran->relasi_guru_mapel_kelas_count }}</td>
                        <td><div class="d-flex flex-wrap gap-2">
                            <a class="abk-button text-decoration-none" href="{{ route('dev.admin.subjects.edit', $mataPelajaran) }}">Ubah</a>
                            <form method="POST" action="{{ route('dev.admin.subjects.status', $mataPelajaran) }}"
                                data-confirm-form
                                data-confirm-title="{{ $willArchive ? 'Arsipkan mata pelajaran?' : 'Aktifkan kembali mata pelajaran?' }}"
                                data-confirm-message="{{ $willArchive ? 'Mata pelajaran tidak ditawarkan untuk pemetaan baru, tetapi catatan dan pemetaan lama tetap tersimpan.' : 'Mata pelajaran akan kembali tersedia untuk pemetaan baru.' }}"
                                data-confirm-subject="{{ $mataPelajaran->nama_mapel }}"
                                data-confirm-button="{{ $willArchive ? 'Arsipkan' : 'Aktifkan' }}"
                                @if ($willArchive) data-confirm-variant="danger" @endif>
                                @csrf
                                @method('PATCH')
                                <button class="abk-button {{ $willArchive ? 'abk-button-danger' : '' }}" type="submit">{{ $willArchive ? 'Arsipkan' : 'Aktifkan' }}</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada mata pelajaran yang sesuai.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$subjects" />
</x-layouts.preview>
