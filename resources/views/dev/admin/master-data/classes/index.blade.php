<x-layouts.preview :role="$role" :roles="$previewRoles" active="classes-mapel" title="Kelola Kelas">
    <x-preview.topbar title="Kelola Kelas" subtitle="Atur kelas aktif dan arsip tahun ajaran tanpa menghapus riwayat siswa." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', 'admin')" :items="[['Kelas & Mata Pelajaran', null], ['Kelas', null]]" />

    <x-preview.flash-messages />

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.classes.index') }}">
            @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            @if (! empty($filters['tahun_ajaran']))<input type="hidden" name="tahun_ajaran" value="{{ $filters['tahun_ajaran'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama kelas, tingkat, atau tahun ajaran...">
        </form>
        <div class="abk-users-count">Total {{ $classes->total() }} kelas</div>
        <a class="abk-secondary-button" href="{{ route('dev.admin.subjects.index') }}">Kelola Mata Pelajaran</a>
        <a class="abk-primary-button" href="{{ route('dev.admin.classes.create') }}">Tambah Kelas</a>
    </section>

    <section class="abk-panel abk-filter-bar abk-compact-filter-bar">
        <form class="abk-master-filter-form" method="GET">
            <input type="hidden" name="cari" value="{{ $filters['cari'] ?? '' }}">
            <div><label for="class-status-filter">Status</label><select class="form-select abk-filter-select" id="class-status-filter" name="status" onchange="this.form.submit()"><option value="">Semua status</option>@foreach($classStatuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
            <div><label for="class-year-filter">Tahun ajaran</label><select class="form-select abk-filter-select" id="class-year-filter" name="tahun_ajaran" onchange="this.form.submit()"><option value="">Semua tahun ajaran</option>@foreach($academicYears as $year)<option value="{{ $year->id_tahun_ajaran }}" @selected((string)($filters['tahun_ajaran'] ?? '') === (string)$year->id_tahun_ajaran)>{{ $year->nama }}</option>@endforeach</select></div>
        </form>
    </section>

    <section class="abk-panel overflow-hidden abk-master-desktop">
        <div class="table-responsive"><table class="table abk-table abk-users-table abk-master-table align-middle">
            <thead><tr><th>Nama Kelas</th><th>Tingkat</th><th>Tahun Ajaran</th><th>Mata Pelajaran</th><th>Status</th><th>Siswa</th><th>Penugasan Guru</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($classes as $kelas)
                    @php($willArchive = $kelas->status_kelas === 'Aktif')
                    <tr>
                        <td class="abk-data-name" data-label="Nama kelas">{{ $kelas->nama_kelas }}</td>
                        <td data-label="Tingkat">{{ $kelas->tingkat }}</td>
                        <td data-label="Tahun ajaran">{{ $kelas->tahun_ajaran }}</td>
                        <td data-label="Mata pelajaran">
                            @forelse ($kelas->mataPelajaran as $subject)
                                <span class="abk-compact-tag">{{ $subject->nama_mapel }}</span>
                            @empty
                                <span class="text-muted">Belum ada mata pelajaran</span>
                            @endforelse
                        </td>
                        <td data-label="Status"><span class="abk-status {{ $willArchive ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $kelas->status_kelas }}</span></td>
                        <td data-label="Siswa">{{ $kelas->siswa_count }}</td>
                        <td data-label="Penugasan guru">{{ $kelas->relasi_guru_mapel_kelas_count }}</td>
                        <td data-label="Aksi"><div class="d-flex flex-wrap gap-2">
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

    <section class="abk-master-accordion" data-master-accordion aria-label="Daftar kelas">
        @forelse ($classes as $kelas)
            @php($willArchive = $kelas->status_kelas === 'Aktif')
            <details class="abk-master-disclosure">
                <summary>
                    <span><small>Nama kelas</small><strong>{{ $kelas->nama_kelas }}</strong></span>
                    <span class="abk-status {{ $willArchive ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $kelas->status_kelas }}</span>
                    <span class="abk-disclosure-chevron" aria-hidden="true"></span>
                </summary>
                <div class="abk-master-disclosure-body">
                    <div><small>Tingkat</small><span>{{ $kelas->tingkat }}</span></div>
                    <div><small>Tahun ajaran</small><span>{{ $kelas->tahun_ajaran }}</span></div>
                    <div class="abk-master-disclosure-wide"><small>Mata pelajaran</small><span class="abk-master-tag-list">@forelse ($kelas->mataPelajaran as $subject)<span class="abk-compact-tag">{{ $subject->nama_mapel }}</span>@empty<span class="text-muted">Belum ada mata pelajaran</span>@endforelse</span></div>
                    <div><small>Siswa</small><span>{{ $kelas->siswa_count }}</span></div>
                    <div><small>Penugasan guru</small><span>{{ $kelas->relasi_guru_mapel_kelas_count }}</span></div>
                    <div class="abk-master-disclosure-wide abk-master-disclosure-actions">
                        <small>Aksi</small>
                        <span>
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
                        </span>
                    </div>
                </div>
            </details>
        @empty
            <div class="abk-panel abk-empty-cell">Belum ada data kelas yang sesuai.</div>
        @endforelse
    </section>
    <x-preview.pagination :paginator="$classes" />
</x-layouts.preview>
