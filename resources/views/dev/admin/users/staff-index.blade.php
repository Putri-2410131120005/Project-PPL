<x-layouts.preview :role="$role" :roles="$previewRoles" active="users" title="Data Guru">
    <x-preview.topbar title="Data Guru" subtitle="Kelola Guru Mata Pelajaran, Tim Inklusi, GPK, dan Kepala Sekolah." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> Data Guru</div>

    <x-preview.flash-messages />

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.staff.index') }}">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama, email, atau NIP...">
        </form>
        <div class="abk-users-count">Total {{ $users->total() }} data guru</div>
        <a class="abk-primary-button" href="{{ route('dev.admin.staff.create') }}">Tambah Guru</a>
    </section>

    <section class="abk-panel abk-filter-bar">
        <span>Peran:</span>
        <a class="abk-filter {{ empty($filters['role']) ? 'active' : '' }}" href="{{ route('dev.admin.staff.index', array_filter(['cari' => $filters['cari'] ?? null])) }}">Semua</a>
        @foreach ($roles as $filterRole)
            <a class="abk-filter {{ (string) ($filters['role'] ?? '') === (string) $filterRole->id_role ? 'active' : '' }}" href="{{ route('dev.admin.staff.index', array_filter(['cari' => $filters['cari'] ?? null, 'role' => $filterRole->id_role])) }}">{{ $filterRole->nama_role }}</a>
        @endforeach
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-users-table align-middle">
            <thead><tr><th>Nama Guru</th><th>NIP / ID</th><th>Email</th><th>Peran</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    @php($willDeactivate = $user->status_akun === 'Aktif')
                    <tr>
                        <td class="abk-data-name">{{ $user->nama }}</td>
                        <td><code>{{ $user->nip }}</code></td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role->nama_role }}</td>
                        <td><span class="abk-status {{ $willDeactivate ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $user->status_akun }}</span></td>
                        <td><div class="d-flex flex-wrap gap-2">
                            <a class="abk-button text-decoration-none" href="{{ route('dev.admin.staff.edit', $user) }}">Ubah</a>
                            <form method="POST" action="{{ route('dev.admin.users.status', $user) }}" data-confirm-form data-confirm-title="{{ $willDeactivate ? 'Nonaktifkan data guru?' : 'Aktifkan data guru?' }}" data-confirm-message="{{ $willDeactivate ? 'Guru tidak dapat masuk hingga akunnya diaktifkan kembali.' : 'Guru dapat kembali menggunakan sistem.' }}" data-confirm-subject="{{ $user->nama }}" data-confirm-button="{{ $willDeactivate ? 'Nonaktifkan' : 'Aktifkan' }}" @if ($willDeactivate) data-confirm-variant="danger" @endif>
                                @csrf
                                @method('PATCH')
                                <button class="abk-button {{ $willDeactivate ? 'abk-button-danger' : '' }}" type="submit">{{ $willDeactivate ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data guru. Tambahkan guru untuk pemetaan kelas, mata pelajaran, atau pendampingan.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
    <x-preview.pagination :paginator="$users" />
</x-layouts.preview>
