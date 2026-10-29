<x-layouts.preview :role="$role" :roles="$previewRoles" active="users" title="Kelola Pengguna">
    <x-preview.topbar title="Kelola Pengguna" subtitle="Master akun seluruh pengguna, termasuk Orang Tua/Wali. Gunakan filter untuk memilih role." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', 'admin')" :items="[['Pengguna', null]]" />

    <x-preview.flash-messages />
    @if (session('temporary_parent_password'))
        <div class="alert alert-warning abk-alert"><strong>Kredensial akun:</strong> username <code>{{ session('temporary_parent_username') }}</code> dan password sementara <code>{{ session('temporary_parent_password') }}</code>. Jika kredensial ini hilang, admin dapat menggunakan Reset Password kembali.</div>
    @endif

    <section class="abk-panel abk-users-toolbar abk-primary-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.users.index') }}">
            @if (! empty($filters['role']))<input type="hidden" name="role" value="{{ $filters['role'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="{{ $isParentFilter ? 'Cari nama atau username...' : 'Cari nama, email, username, atau NIP...' }}">
        </form>
        <div class="abk-users-count">Total {{ $users->total() }} pengguna</div>
        <a class="abk-primary-button" href="{{ route('dev.admin.users.create') }}">Tambah Pengguna</a>
    </section>

    <section class="abk-panel abk-filter-bar abk-compact-filter-bar">
        <form class="abk-compact-filter-form" method="GET" action="{{ route('dev.admin.users.index') }}">
            <input type="hidden" name="cari" value="{{ $filters['cari'] ?? '' }}">
            <label for="role-filter">Role pengguna</label>
            <select class="form-select abk-filter-select" id="role-filter" name="role" onchange="this.form.submit()">
                <option value="">Semua role</option>
                @foreach ($roles as $filterRole)
                    <option value="{{ $filterRole->id_role }}" @selected((string) ($filters['role'] ?? '') === (string) $filterRole->id_role)>{{ $filterRole->nama_role }}</option>
                @endforeach
            </select>
        </form>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-users-table align-middle">
            <thead><tr>
                <th>Nama Pengguna</th>
                @unless ($isParentFilter)
                    <th>Email</th>
                @endunless
                @if ($isParentFilter)
                    <th>Username &amp; Keamanan</th>
                @endif
                <th>Role</th><th>Status</th><th>Aksi</th>
            </tr></thead><tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="abk-data-name">{{ $user->nama }}</td>
                    @unless ($isParentFilter)
                        <td>{{ $user->email ?: '—' }}</td>
                    @endunless
                    @if ($isParentFilter)
                        <td>
                            <div><strong>Username:</strong> {{ $user->username ?: 'Belum tersedia' }}</div>
                            @if ($user->visible_temporary_password)
                                <div class="mt-1"><strong>Password Sementara:</strong> {{ $user->visible_temporary_password }}</div>
                            @elseif ($user->wajib_ganti_password === false)
                                <div class="small text-muted mt-1"><strong>Password:</strong> Sudah diubah</div>
                            @else
                                <div class="small text-muted mt-1"><strong>Password:</strong> Tidak dapat ditampilkan; gunakan Reset Password.</div>
                            @endif
                            <form class="mt-2" method="POST" action="{{ route('dev.admin.users.reset-password', $user) }}"
                                data-confirm-form
                                data-confirm-title="Reset password Orang Tua/Wali?"
                                data-confirm-message="Password lama tidak dapat digunakan lagi. Sistem akan membuat ulang password sementara dari data siswa."
                                data-confirm-subject="{{ $user->nama }} / {{ $user->username }}"
                                data-confirm-button="Reset Password"
                                data-confirm-variant="danger">
                                @csrf
                                @method('PATCH')
                                <button class="abk-button abk-button-danger" type="submit">Reset Password</button>
                            </form>
                        </td>
                    @endif
                    <td>{{ $user->is_super_admin ? 'Super Admin' : $user->role->nama_role }}</td>
                    <td><span class="abk-status {{ $user->status_akun === 'Aktif' ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $user->status_akun }}</span></td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="abk-button text-decoration-none" href="{{ route('dev.admin.users.edit', $user) }}">Ubah</a>
                            @if (! $user->is_super_admin && ! in_array($user->role->nama_role, ['Admin', 'Admin / Pengelola'], true))
                            @php($willDeactivate = $user->status_akun === 'Aktif')
                            <form method="POST" action="{{ route('dev.admin.users.status', $user) }}"
                                data-confirm-form
                                data-confirm-title="{{ $willDeactivate ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?' }}"
                                data-confirm-message="{{ $willDeactivate ? 'Pengguna tidak dapat masuk ke sistem sampai akun diaktifkan kembali.' : 'Pengguna dapat kembali masuk dan menggunakan sistem.' }}"
                                data-confirm-subject="{{ $user->nama }} / {{ $user->role->nama_role }}"
                                data-confirm-button="{{ $willDeactivate ? 'Nonaktifkan' : 'Aktifkan' }}"
                                @if ($willDeactivate) data-confirm-variant="danger" @endif>
                                @csrf
                                @method('PATCH')
                                <button class="abk-button {{ $willDeactivate ? 'abk-button-danger' : '' }}" type="submit">{{ $willDeactivate ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                            @elseif ($user->is_super_admin)
                                <span class="abk-status abk-status-verified">Akun Sistem</span>
                            @else
                                <span class="abk-status abk-status-inactive">Dikelola Developer</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada pengguna yang sesuai.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <x-preview.pagination :paginator="$users" />
</x-layouts.preview>
