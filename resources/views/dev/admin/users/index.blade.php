<x-layouts.preview :role="$role" :roles="$previewRoles" active="users" title="Kelola Pengguna">
    <x-preview.topbar title="Kelola Pengguna" subtitle="Master akun seluruh pengguna, termasuk Orang Tua/Wali. Gunakan filter untuk memilih role." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> Pengguna</div>

    @if (session('success'))<div class="alert alert-success abk-alert">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger abk-alert">{{ session('error') }}</div>@endif
    @if (session('temporary_parent_password'))
        <div class="alert alert-warning abk-alert"><strong>Kredensial akun:</strong> username <code>{{ session('temporary_parent_username') }}</code> dan password sementara <code>{{ session('temporary_parent_password') }}</code>. Jika kredensial ini hilang, admin dapat menggunakan Reset Password kembali.</div>
    @endif

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.users.index') }}">
            @if (! empty($filters['role']))<input type="hidden" name="role" value="{{ $filters['role'] }}">@endif
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama, email, username, atau NIP...">
        </form>
        <div class="abk-users-count">Total {{ $users->total() }} pengguna</div>
        <a class="abk-primary-button" href="{{ route('dev.admin.users.create') }}">Tambah Pengguna</a>
    </section>

    <section class="abk-panel abk-filter-bar">
        <span>Role:</span>
        <a class="abk-filter {{ empty($filters['role']) ? 'active' : '' }}" href="{{ route('dev.admin.users.index', array_filter(['cari' => $filters['cari'] ?? null])) }}">Semua</a>
        @foreach ($roles as $filterRole)
            <a class="abk-filter {{ (string) ($filters['role'] ?? '') === (string) $filterRole->id_role ? 'active' : '' }}" href="{{ route('dev.admin.users.index', array_filter(['cari' => $filters['cari'] ?? null, 'role' => $filterRole->id_role])) }}">{{ $filterRole->nama_role }}</a>
        @endforeach
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table abk-users-table align-middle"><thead><tr><th>Nama Pengguna</th><th>Email</th>@if($isParentFilter)<th>Username &amp; Keamanan</th>@endif<th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="abk-data-name">{{ $user->nama }}</td>
                    <td>{{ $user->email ?: '—' }}</td>
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
                    <td>{{ $user->role->nama_role }}</td>
                    <td><span class="abk-status {{ $user->status_akun === 'Aktif' ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $user->status_akun }}</span></td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="abk-button text-decoration-none" href="{{ route('dev.admin.users.edit', $user) }}">Ubah</a>
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
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $isParentFilter ? 6 : 5 }}" class="text-center py-4 text-muted">Belum ada pengguna yang sesuai.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <x-preview.pagination :paginator="$users" />
</x-layouts.preview>
