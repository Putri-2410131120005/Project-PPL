<x-layouts.preview :role="$role" :roles="$previewRoles" active="academic-years" title="Tahun Ajaran">
    <x-preview.topbar title="Tahun Ajaran" subtitle="Atur periode akademik yang menjadi acuan kelas, rekap, dan laporan." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.preview', 'admin')" :items="[['Tahun Ajaran', null]]" />
    <x-preview.flash-messages />

    <section class="abk-panel abk-users-toolbar">
        <form class="abk-search-form" method="GET"><input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari tahun ajaran..."></form>
        <div class="abk-users-count">Total {{ $academicYears->total() }} periode</div>
        <a class="abk-primary-button" href="{{ route('dev.admin.academic-years.create') }}">Tambah Tahun Ajaran</a>
    </section>

    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle">
        <thead><tr><th>Tahun Ajaran</th><th>Semester Ganjil</th><th>Semester Genap</th><th>Kelas</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($academicYears as $year)<tr>
            <td class="abk-data-name">{{ $year->nama }}</td>
            <td>{{ $year->mulai_ganjil->format('d/m/Y') }}–{{ $year->selesai_ganjil->format('d/m/Y') }}</td>
            <td>{{ $year->mulai_genap->format('d/m/Y') }}–{{ $year->selesai_genap->format('d/m/Y') }}</td>
            <td>{{ $year->kelas_count }} kelas</td>
            <td><span class="abk-status {{ $year->status === 'Aktif' ? 'abk-status-verified' : 'abk-status-inactive' }}">{{ $year->status }}</span></td>
            <td><div class="d-flex flex-wrap gap-2"><a class="abk-button text-decoration-none" href="{{ route('dev.admin.academic-years.edit', $year) }}">Ubah</a>
                @if($year->status !== 'Aktif')<form method="POST" action="{{ route('dev.admin.academic-years.activate', $year) }}" data-confirm-form data-confirm-title="Aktifkan tahun ajaran?" data-confirm-message="Periode ini menjadi pilihan utama seluruh modul. Periode aktif sebelumnya akan dinonaktifkan." data-confirm-button="Aktifkan">@csrf @method('PATCH')<button class="abk-primary-button" type="submit">Aktifkan</button></form>@endif
            </div></td>
        </tr>@empty<tr><td colspan="6" class="abk-empty-cell">Belum ada tahun ajaran. Tambahkan periode sebelum membuat kelas.</td></tr>@endforelse</tbody>
    </table></div></section>
    <x-preview.pagination :paginator="$academicYears" />
</x-layouts.preview>
