<x-layouts.preview :role="$role" :roles="$previewRoles" active="reports" title="Laporan Perkembangan">
    <x-preview.topbar title="Laporan Perkembangan" subtitle="Laporan bulanan muncul otomatis setelah bulan berakhir. Status Tersedia berarti laporan telah dipublikasikan ke Kepala Sekolah." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.gpk.dashboard')" :items="[['Laporan', null]]" />
    <x-preview.flash-messages />
    <section class="abk-panel abk-gpk-filter-panel"><form method="GET" class="abk-gpk-filter-form abk-gpk-period-filter"><select class="form-select" name="semester"><option value="ganjil" @selected($filters['semester'] === 'ganjil')>Semester Ganjil</option><option value="genap" @selected($filters['semester'] === 'genap')>Semester Genap</option></select><select class="form-select" name="tahun_ajaran">@foreach($schoolYears as $year)<option value="{{ $year }}" @selected($filters['tahun_ajaran'] === $year)>Tahun Ajaran {{ $year }}</option>@endforeach</select><button class="abk-dark-button" type="submit">Terapkan</button></form><div class="abk-users-count">{{ $period }}</div></section>
    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle mb-0">
        <thead><tr><th>Periode</th><th>Jumlah Siswa</th><th>Catatan Terverifikasi</th><th>Diperbarui</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($reports as $report)<tr>
            <td class="abk-data-name">{{ $report->periode_bulan->locale('id')->translatedFormat('F Y') }}</td>
            <td>{{ $report->jumlah_siswa }} siswa</td>
            <td>{{ $report->jumlah_catatan }} catatan</td>
            <td>{{ $report->tanggal_dibuat->locale('id')->translatedFormat('d M Y') }}</td>
            <td><x-preview.status-badge :status="$report->status_laporan" /></td>
            <td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.reports.show',$report) }}">{{ $report->status_laporan === 'Tersedia' ? 'Lihat Laporan' : 'Kelola Laporan' }}</a></td>
        </tr>@empty<tr><td colspan="6" class="abk-empty-cell">Belum ada laporan bulanan. Laporan pertama akan muncul otomatis setelah akhir bulan yang memiliki catatan terverifikasi.</td></tr>@endforelse</tbody>
    </table></div></section><x-preview.pagination :paginator="$reports" />
</x-layouts.preview>
