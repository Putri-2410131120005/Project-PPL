<x-layouts.preview :role="$role" :roles="$previewRoles" active="reports" title="Laporan Perkembangan">
    <x-preview.topbar
        :title="$laporan->status_laporan === 'Tersedia' ? 'Laporan Dipublikasikan' : 'Laporan Perkembangan'"
        :subtitle="$laporan->status_laporan === 'Tersedia' ? 'Laporan sudah dipublikasikan dan dapat dipantau Kepala Sekolah.' : 'Tinjau ringkasan siswa sebelum laporan bulanan dipublikasikan.'"
        :profile="$profile"
    />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span><a href="{{ route('dev.gpk.reports.index') }}">Laporan</a><span>/</span>{{ $laporan->periode_bulan->locale('id')->translatedFormat('F Y') }}</div>
    <x-preview.flash-messages />
    @if ($errors->any())<div class="abk-alert abk-alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="abk-gpk-page-actions">
        @if ($laporan->status_laporan !== 'Tersedia')
            <form method="POST" action="{{ route('dev.gpk.reports.publish', $laporan) }}" data-confirm-form data-confirm-title="Publikasikan laporan?" data-confirm-message="Laporan bulanan akan dikirim dan tersedia untuk Kepala Sekolah." data-confirm-button="Publikasikan">
                @csrf
                <button class="abk-dark-button" type="submit">Publikasikan</button>
            </form>
        @endif
        <a class="abk-outline-button" href="{{ route('dev.gpk.reports.pdf.download', $laporan) }}">Unduh PDF</a>
        <a class="abk-dark-button" href="{{ route('dev.gpk.reports.pdf.preview', $laporan) }}">Pratinjau PDF</a>
    </div>

    <section class="abk-panel abk-gpk-summary-grid">
        <div><small>Periode</small><strong>{{ $laporan->periode_bulan->locale('id')->translatedFormat('F Y') }}</strong></div>
        <div><small>Jumlah Siswa</small><strong>{{ $laporan->jumlah_siswa }} siswa</strong></div>
        <div><small>Catatan Terverifikasi</small><strong>{{ $laporan->jumlah_catatan }} catatan</strong></div>
        <div><small>Status</small><strong>{{ $laporan->status_laporan }}</strong></div>
    </section>

    @if ($laporan->tanggal_publikasi)
        <section class="abk-panel abk-gpk-editor"><h2 class="h6 mb-2">Riwayat Publikasi</h2><p class="mb-0">Dipublikasikan oleh <strong>{{ $laporan->publikator?->nama ?: '—' }}</strong> — {{ $laporan->publikator?->jabatan_gpk ?: 'GPK' }} pada {{ $laporan->tanggal_publikasi->locale('id')->translatedFormat('d M Y H:i') }}.</p></section>
    @endif

    @if ($selectedStudent)
        <x-reports.student-summary :student="$selectedStudent" :close-url="route('dev.gpk.reports.show', $laporan)" />
    @endif

    <section class="abk-panel overflow-hidden">
        <div class="abk-report-table-heading"><div><h2>Daftar ringkasan siswa</h2><p>Ringkasan disusun dari catatan Guru Mata Pelajaran dan Tim Inklusi yang telah diverifikasi GPK.</p></div></div>
        <div class="table-responsive"><table class="table abk-table align-middle mb-0">
            <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Catatan Terverifikasi</th><th>Ringkasan Perkembangan</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($students as $student)
                <tr>
                    <td class="abk-data-name">{{ $student['nama_siswa'] }}</td>
                    <td>{{ $student['kelas'] }}</td>
                    <td>{{ $student['jumlah_catatan'] }} catatan</td>
                    <td>{{ \Illuminate\Support\Str::limit(collect($student['ringkasan'] ?? [])->filter()->first() ?: 'Belum ada catatan terverifikasi pada periode ini.', 110) }}</td>
                    <td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.reports.show', ['laporan' => $laporan, 'siswa' => $student['id_siswa']]) }}">Lihat Ringkasan</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="abk-empty-cell">Tidak ada data siswa dalam laporan ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</x-layouts.preview>
