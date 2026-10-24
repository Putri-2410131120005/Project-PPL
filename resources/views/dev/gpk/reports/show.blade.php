<x-layouts.preview :role="$role" :roles="$previewRoles" active="reports" title="Laporan Perkembangan">
    <x-preview.topbar :title="$laporan->status_laporan === 'Tersedia' ? 'Laporan Dipublikasikan' : 'Laporan Perkembangan'" :subtitle="$laporan->status_laporan === 'Tersedia' ? 'Sudah dipublikasikan oleh GPK dan tersedia untuk Kepala Sekolah.' : 'Draf otomatis dari catatan terverifikasi; belum tersedia untuk Kepala Sekolah.'" :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span><a href="{{ route('dev.gpk.reports.index') }}">Laporan</a><span>/</span>{{ $laporan->periode_bulan->locale('id')->translatedFormat('F Y') }}</div>
    <x-preview.flash-messages />
    @if($errors->any())<div class="abk-alert abk-alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="abk-gpk-page-actions">
        @if($laporan->status_laporan !== 'Tersedia')
            <form method="POST" action="{{ route('dev.gpk.reports.publish',$laporan) }}" data-confirm-form data-confirm-title="Publikasikan laporan?" data-confirm-message="Laporan bulanan akan dikirim dan tersedia untuk Kepala Sekolah." data-confirm-button="Publikasikan">@csrf<button class="abk-dark-button" type="submit">Publikasikan</button></form>
        @endif
        <a class="abk-outline-button" href="{{ route('dev.gpk.reports.pdf.download', $laporan) }}">Unduh PDF</a>
        <a class="abk-dark-button" href="{{ route('dev.gpk.reports.pdf.preview', $laporan) }}">Pratinjau PDF</a>
    </div>
    <section class="abk-panel abk-gpk-summary-grid">
        <div><small>Periode</small><strong>{{ $laporan->periode_bulan->locale('id')->translatedFormat('F Y') }}</strong></div>
        <div><small>Jenis Laporan</small><strong>Laporan Perkembangan Siswa ABK</strong></div>
        <div><small>Siswa</small><strong>Semua Siswa</strong></div>
        <div><small>Status</small><strong>{{ $laporan->status_laporan }}</strong></div>
    </section>
    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle mb-0">
        <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Jumlah Catatan Terverifikasi</th><th>Ringkasan Perkembangan</th></tr></thead>
        <tbody>@forelse($students as $student)<tr>
            <td class="abk-data-name">{{ $student['nama_siswa'] }}</td><td>{{ $student['kelas'] }}</td><td>{{ $student['jumlah_catatan'] }} catatan</td>
            <td><details class="abk-gpk-details"><summary class="abk-button">Lihat Ringkasan</summary><div>@forelse($student['ringkasan'] as $summary)<p>{{ $summary }}</p>@empty<p>Belum ada catatan terverifikasi pada bulan ini.</p>@endforelse</div></details></td>
        </tr>@empty<tr><td colspan="4" class="abk-empty-cell">Tidak ada data siswa dalam laporan ini.</td></tr>@endforelse</tbody>
    </table></div></section>
</x-layouts.preview>
