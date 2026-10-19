<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" title="Kelola Data Siswa ABK">
    <x-preview.topbar title="Kelola Data Siswa ABK" subtitle="Kelola data induk siswa, kelas, kebutuhan khusus, dan status keaktifan." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> Siswa ABK</div>

    <x-preview.flash-messages />

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.students.index') }}">
            @if (! empty($filters['kelas']))<input type="hidden" name="kelas" value="{{ $filters['kelas'] }}">@endif
            @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            @if (($filters['urut_nama'] ?? 'az') !== 'az')<input type="hidden" name="urut_nama" value="{{ $filters['urut_nama'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama atau NIS siswa...">
        </form>
        <div class="abk-users-count">Total {{ $students->total() }} siswa</div>
        <a class="abk-primary-button" href="{{ route('dev.admin.students.create') }}">Tambah Siswa ABK</a>
    </section>

    <section class="abk-panel abk-filter-bar">
        <form class="abk-student-filter-form" method="GET" action="{{ route('dev.admin.students.index') }}">
            <input type="hidden" name="cari" value="{{ $filters['cari'] ?? '' }}">
            <div class="abk-filter-field">
                <label for="kelas">Kelas</label>
                <select class="form-select abk-filter-select" id="kelas" name="kelas">
                    <option value="">Semua kelas</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id_kelas }}" @selected((string) ($filters['kelas'] ?? '') === (string) $class->id_kelas)>{{ $class->tingkat }} — {{ $class->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="abk-filter-field">
                <label for="status">Status siswa</label>
                <select class="form-select abk-filter-select" id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="abk-filter-actions">
                <button class="abk-button" type="submit">Terapkan</button>
                @if (! empty($filters['cari']) || ! empty($filters['kelas']) || ! empty($filters['status']) || ($filters['urut_nama'] ?? 'az') !== 'az')
                    <a class="abk-filter" href="{{ route('dev.admin.students.index') }}">Reset</a>
                @endif
            </div>
        </form>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-users-table align-middle"><thead><tr><th>NIS</th><th aria-sort="{{ ($filters['urut_nama'] ?? 'az') === 'za' ? 'descending' : 'ascending' }}">
            <a
                class="abk-table-sort"
                href="{{ route('dev.admin.students.index', array_merge(request()->except(['page', 'urut_nama']), ['urut_nama' => ($filters['urut_nama'] ?? 'az') === 'az' ? 'za' : 'az'])) }}"
                title="Urutkan nama {{ ($filters['urut_nama'] ?? 'az') === 'az' ? 'Z–A' : 'A–Z' }}"
            >
                <span>Nama Siswa</span>
                <span class="abk-sort-direction" aria-hidden="true">{{ ($filters['urut_nama'] ?? 'az') === 'az' ? 'A–Z ↑' : 'Z–A ↓' }}</span>
            </a>
        </th><th>Kelas</th><th>Kebutuhan Khusus</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->nis }}</td>
                    <td class="abk-data-name">{{ $student->nama_siswa }}</td>
                    <td>{{ $student->kelas?->tingkat }}{{ $student->kelas ? ' — '.$student->kelas->nama_kelas : '—' }}</td>
                    <td>{{ $student->kebutuhan_khusus ?: '—' }}</td>
                    <td><span class="abk-status {{ $student->status_siswa === 'Aktif' ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $student->status_siswa }}</span></td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="abk-button text-decoration-none" href="{{ route('dev.admin.students.edit', $student) }}">Ubah</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data siswa yang sesuai.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>

    <x-preview.pagination :paginator="$students" />
</x-layouts.preview>
