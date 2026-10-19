<x-layouts.preview :role="$role" :roles="$previewRoles" active="classes-mapel" title="Kelola Kelas">
    <x-preview.topbar title="Kelola Kelas" subtitle="Atur kelas aktif dan arsip tahun ajaran tanpa menghapus riwayat siswa." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> Kelas &amp; Mata Pelajaran <span>/</span> Kelas</div>

    @if (session('success'))<div class="alert alert-success abk-alert">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger abk-alert">{{ session('error') }}</div>@endif

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.classes.index') }}">
            @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama kelas, tingkat, atau tahun ajaran...">
        </form>
        <div class="abk-users-count">Total {{ $classes->total() }} kelas</div>
        <a class="abk-secondary-button" href="{{ route('dev.admin.subjects.index') }}">Kelola Mata Pelajaran</a>
        <a class="abk-primary-button" href="{{ route('dev.admin.classes.create') }}">Tambah Kelas</a>
    </section>

    <section class="abk-panel abk-filter-bar">
        <span>Status:</span>
        <a class="abk-filter {{ empty($filters['status']) ? 'active' : '' }}" href="{{ route('dev.admin.classes.index', array_filter(['cari' => $filters['cari'] ?? null])) }}">Semua</a>
        @foreach ($classStatuses as $status)
            <a class="abk-filter {{ ($filters['status'] ?? '') === $status ? 'active' : '' }}" href="{{ route('dev.admin.classes.index', array_filter(['cari' => $filters['cari'] ?? null, 'status' => $status])) }}">{{ $status }}</a>
        @endforeach
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-users-table align-middle">
            <thead><tr><th>Nama Kelas</th><th>Tingkat</th><th>Tahun Ajaran</th><th>Mata Pelajaran</th><th>Status</th><th>Siswa</th><th>Penugasan Guru</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($classes as $kelas)
                    @php($willArchive = $kelas->status_kelas === 'Aktif')
                    <tr>
                        <td class="abk-data-name">{{ $kelas->nama_kelas }}</td>
                        <td>{{ $kelas->tingkat }}</td>
                        <td>{{ $kelas->tahun_ajaran }}</td>
                        <td>
                            @forelse ($kelas->mataPelajaran as $subject)
                                <span class="abk-compact-tag">{{ $subject->nama_mapel }}</span>
                            @empty
                                <span class="text-muted">Belum ada mata pelajaran</span>
                            @endforelse
                        </td>
                        <td><span class="abk-status {{ $willArchive ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $kelas->status_kelas }}</span></td>
                        <td>{{ $kelas->siswa_count }}</td>
                        <td>{{ $kelas->relasi_guru_mapel_kelas_count }}</td>
                        <td><div class="d-flex flex-wrap gap-2">
                            <a class="abk-button text-decoration-none" href="{{ route('dev.admin.classes.edit', $kelas) }}">Ubah</a>
                            <form method="POST" action="{{ route('dev.admin.classes.status', $kelas) }}"
                                data-confirm-form
                                data-confirm-title="{{ $willArchive ? 'Arsipkan kelas?' : 'Aktifkan kembali kelas?' }}"
                                data-confirm-message="{{ $willArchive ? 'Kelas tidak ditawarkan untuk siswa atau pemetaan baru, tetapi seluruh riwayatnya tetap tersimpan.' : 'Kelas akan kembali tersedia untuk pengelolaan siswa dan pemetaan baru.' }}"
                                data-confirm-subject="{{ $kelas->nama_kelas }} — {{ $kelas->tahun_ajaran }}"
                                data-confirm-button="{{ $willArchive ? 'Arsipkan' : 'Aktifkan' }}"
                                @if ($willArchive) data-confirm-variant="danger" @endif>
                                @csrf
                                @method('PATCH')
                                <button class="abk-button {{ $willArchive ? 'abk-button-danger' : '' }}" type="submit">{{ $willArchive ? 'Arsipkan' : 'Aktifkan' }}</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data kelas yang sesuai.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$classes" />
</x-layouts.preview>
