<x-layouts.preview :role="$role" :roles="$previewRoles" active="users" title="Data Orang Tua/Wali">
    <x-preview.topbar title="Data Orang Tua/Wali" subtitle="Kelola akun wali yang ditautkan ke siswa." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', 'admin')" :items="[['Pengguna', route('dev.admin.users.index')], ['Orang Tua/Wali', null]]" />

    <x-preview.flash-messages />
    @if (session('temporary_parent_password'))
        <div class="alert alert-warning abk-alert">
            <strong>Simpan kredensial ini sekarang:</strong>
            username <code>{{ session('temporary_parent_username') }}</code> dan password sementara <code>{{ session('temporary_parent_password') }}</code>
            Password hanya ditampilkan satu kali.
        </div>
    @endif

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.parents.index') }}">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama, email, atau NIS...">
        </form>
        <div class="abk-users-count">Total {{ $parents->total() }} akun</div>
        <a class="abk-primary-button" href="{{ route('dev.admin.users.create') }}">Tambah Pengguna</a>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table abk-table abk-users-table align-middle">
                <thead><tr><th>Orang Tua/Wali</th><th>Username</th><th>Siswa Terhubung</th><th>Nomor HP</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($parents as $parentProfile)
                        @php($account = $parentProfile->pengguna)
                        <tr>
                            <td><div class="abk-data-name">{{ $account->nama }}</div><div class="text-muted small">{{ $account->email ?: 'Email tidak diisi' }}</div></td>
                            <td><code>{{ $account->username ?: 'Belum ada NIS' }}</code></td>
                            <td>
                                @forelse ($parentProfile->relasiSiswa as $relation)
                                    <div>{{ $relation->siswa->nis }} - {{ $relation->siswa->nama_siswa }} <span class="text-muted">({{ $relation->hubungan }})</span></div>
                                @empty
                                    <span class="text-muted">Belum dipetakan</span>
                                @endforelse
                            </td>
                            <td>{{ $parentProfile->no_telepon ?: 'Diisi saat login pertama' }}</td>
                            <td><span class="abk-status {{ $account->status_akun === 'Aktif' ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $account->status_akun }}</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="abk-button text-decoration-none" href="{{ route('dev.admin.users.edit', $account) }}">Ubah</a>
                                    <a class="abk-button text-decoration-none" href="{{ route('dev.admin.mappings.orang-tua-siswa.index') }}">Pemetaan</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada akun Orang Tua/Wali. Tambahkan akun setelah data siswa tersedia.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <x-preview.pagination :paginator="$parents" />
</x-layouts.preview>
