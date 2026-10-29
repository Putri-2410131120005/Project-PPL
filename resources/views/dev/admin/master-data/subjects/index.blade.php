<x-layouts.preview :role="$role" :roles="$previewRoles" active="classes-mapel" title="Kelola Mata Pelajaran">
    <x-preview.topbar title="Kelola Mata Pelajaran" subtitle="Atur mata pelajaran aktif dan arsip tanpa menghapus catatan perkembangan." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', 'admin')" :items="[['Kelas & Mata Pelajaran', null], ['Mata Pelajaran', null]]" />

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

    <section class="abk-panel abk-filter-bar abk-compact-filter-bar">
        <form class="abk-compact-filter-form" method="GET">
            <input type="hidden" name="cari" value="{{ $filters['cari'] ?? '' }}">
            <label for="subject-status-filter">Status</label>
            <select class="form-select abk-filter-select" id="subject-status-filter" name="status" onchange="this.form.submit()"><option value="">Semua status</option>@foreach($subjectStatuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select>
        </form>
    </section>

    <section class="abk-panel overflow-hidden abk-master-desktop">
        <div class="table-responsive"><table class="table abk-table abk-users-table abk-master-table align-middle">
            <thead><tr><th>Mata Pelajaran</th><th>Kelas Terkait</th><th>Status</th><th>Catatan Perkembangan</th><th>Penugasan Guru</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($subjects as $mataPelajaran)
                    @php($willArchive = $mataPelajaran->status_mapel === 'Aktif')
                    <tr>
                        <td class="abk-data-name" data-label="Mata pelajaran">{{ $mataPelajaran->nama_mapel }}</td>
                        <td data-label="Kelas terkait">
                            @forelse ($mataPelajaran->kelas as $class)
                                <span class="abk-compact-tag">{{ $class->tingkat }} — {{ $class->nama_kelas }}</span>
                            @empty
                                <span class="text-muted">Belum ada kelas</span>
                            @endforelse
                        </td>
                        <td data-label="Status"><span class="abk-status {{ $willArchive ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $mataPelajaran->status_mapel }}</span></td>
                        <td data-label="Catatan perkembangan">{{ $mataPelajaran->catatan_perkembangan_count }}</td>
                        <td data-label="Penugasan guru">{{ $mataPelajaran->relasi_guru_mapel_kelas_count }}</td>
                        <td data-label="Aksi"><div class="d-flex flex-wrap gap-2">
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

    <section class="abk-master-accordion" data-master-accordion aria-label="Daftar mata pelajaran">
        @forelse ($subjects as $mataPelajaran)
            @php($willArchive = $mataPelajaran->status_mapel === 'Aktif')
            <details class="abk-master-disclosure">
                <summary>
                    <span><small>Mata pelajaran</small><strong>{{ $mataPelajaran->nama_mapel }}</strong></span>
                    <span class="abk-status {{ $willArchive ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $mataPelajaran->status_mapel }}</span>
                    <span class="abk-disclosure-chevron" aria-hidden="true"></span>
                </summary>
                <div class="abk-master-disclosure-body">
                    <div class="abk-master-disclosure-wide"><small>Kelas terkait</small><span class="abk-master-tag-list">@forelse ($mataPelajaran->kelas as $class)<span class="abk-compact-tag">{{ $class->tingkat }} — {{ $class->nama_kelas }}</span>@empty<span class="text-muted">Belum ada kelas</span>@endforelse</span></div>
                    <div><small>Catatan perkembangan</small><span>{{ $mataPelajaran->catatan_perkembangan_count }}</span></div>
                    <div><small>Penugasan guru</small><span>{{ $mataPelajaran->relasi_guru_mapel_kelas_count }}</span></div>
                    <div class="abk-master-disclosure-wide abk-master-disclosure-actions">
                        <small>Aksi</small>
                        <span>
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
                        </span>
                    </div>
                </div>
            </details>
        @empty
            <div class="abk-panel abk-empty-cell">Belum ada mata pelajaran yang sesuai.</div>
        @endforelse
    </section>
    <x-preview.pagination :paginator="$subjects" />
</x-layouts.preview>
