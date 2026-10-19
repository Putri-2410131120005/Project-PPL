<x-layouts.preview :role="$role" :roles="$previewRoles" active="mappings" title="Hubungan Orang Tua dan Siswa">
    <x-preview.topbar title="Hubungan Orang Tua dan Siswa" subtitle="Tinjau akun Orang Tua/Wali dan siswa yang menjadi tanggung jawabnya." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> Hubungan Data <span>/</span> Orang Tua Siswa</div>

    @if (session('success'))<div class="alert alert-success abk-alert">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger abk-alert">{{ session('error') }}</div>@endif

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET" action="{{ route('dev.admin.mappings.orang-tua-siswa.index') }}">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama Orang Tua/Wali, email, NIS, atau siswa...">
        </form>
        <div class="abk-users-count">Total {{ $mappings->total() }} pemetaan</div>
    </section>

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table abk-table align-middle mb-0">
                <thead><tr><th>Orang Tua/Wali</th><th>Siswa ABK</th><th>Hubungan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($mappings as $mapping)
                        <tr>
                            <td><div class="abk-data-name">{{ $mapping->orangTua?->pengguna?->nama }}</div><div class="small text-muted">{{ $mapping->orangTua?->pengguna?->email }}</div></td>
                            <td><div class="abk-data-name">{{ $mapping->siswa?->nama_siswa }}</div><div class="small text-muted">NIS {{ $mapping->siswa?->nis }}</div></td>
                            <td><span class="abk-status abk-status-verified">{{ $mapping->hubungan }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('dev.admin.mappings.orang-tua-siswa.destroy', $mapping) }}" data-confirm-form data-confirm-title="Lepaskan pemetaan Orang Tua/Wali?" data-confirm-message="Akun Orang Tua/Wali tidak lagi terhubung dengan siswa ini. Riwayat lain tetap tersimpan." data-confirm-subject="{{ $mapping->orangTua?->pengguna?->nama }} — {{ $mapping->siswa?->nama_siswa }}" data-confirm-button="Lepaskan Pemetaan" data-confirm-variant="destructive">
                                    @csrf
                                    @method('DELETE')
                                    <button class="abk-button abk-button-danger" type="submit">Lepaskan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada pemetaan siswa dengan Orang Tua/Wali.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <x-preview.pagination :paginator="$mappings" />
</x-layouts.preview>
