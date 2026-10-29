<x-layouts.preview :role="$role" :roles="$previewRoles" active="recaps" title="Detail Rekap Perkembangan Siswa">
    <x-preview.topbar title="Detail Rekap Perkembangan Siswa" subtitle="Riwayat perkembangan siswa selama satu semester berdasarkan catatan yang telah terverifikasi." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.gpk.dashboard')" :items="[['Rekap', route('dev.gpk.recaps.index')], [$siswa->nama_siswa, null]]" />
    <div class="abk-gpk-page-actions abk-export-actions">
        <a class="abk-outline-button text-decoration-none" href="{{ route('dev.gpk.recaps.export', ['siswa' => $siswa, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Unduh Excel</a>
        <a class="abk-outline-button" href="{{ route('dev.gpk.recaps.pdf.download', ['siswa' => $siswa, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Unduh PDF</a>
        <a class="abk-dark-button" href="{{ route('dev.gpk.recaps.pdf.preview', ['siswa' => $siswa, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Pratinjau PDF</a>
    </div>
    <section class="abk-panel abk-gpk-summary-grid">
        <div><small>Periode</small><strong>{{ $period }}</strong></div>
        <div><small>Jenis Rekap</small><strong>Rekap Perkembangan per Siswa</strong></div>
        <div><small>Siswa</small><strong>{{ $siswa->nama_siswa }}</strong></div>
    </section>
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle mb-0">
            <thead><tr><th>Tanggal</th><th>Sumber</th><th>Rekomendasi / Tindak Lanjut</th><th>Ringkasan Peristiwa</th></tr></thead>
            <tbody>@forelse($records as $record)<tr>
                <td class="abk-data-name text-nowrap">{{ $record['date']->locale('id')->translatedFormat('d M Y') }}</td>
                <td>{{ $record['source'] }}</td>
                <td>{{ $record['recommendation'] }}</td>
                <td>{{ $record['summary'] }}</td>
            </tr>@empty<tr><td colspan="4" class="abk-empty-cell">Belum ada catatan terverifikasi pada semester ini.</td></tr>@endforelse</tbody>
        </table></div>
    </section>
</x-layouts.preview>
