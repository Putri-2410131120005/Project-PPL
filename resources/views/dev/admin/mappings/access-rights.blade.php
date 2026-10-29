<x-layouts.preview :role="$role" :roles="$previewRoles" active="mappings" title="Hak Akses Pengguna">
    <x-preview.topbar title="Hak Akses Pengguna" subtitle="Ringkasan peran yang tersedia dan jumlah akun yang ditetapkan pada setiap peran." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', 'admin')" :items="[['Pemetaan Data', null], ['Hak Akses Pengguna', null]]" />

    <section class="abk-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table abk-table align-middle mb-0">
                <thead><tr><th>Peran</th><th>Jumlah Akun</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @forelse ($roles as $itemRole)
                        <tr>
                            <td class="abk-data-name">{{ $itemRole->nama_role }}</td>
                            <td><span class="abk-status abk-status-verified">{{ $itemRole->pengguna_count }} akun</span></td>
                            <td class="text-muted">Akses modul mengikuti peran akun.</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada peran yang tersedia.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <x-preview.pagination :paginator="$roles" />
</x-layouts.preview>
