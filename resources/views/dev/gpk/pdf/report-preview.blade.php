<x-layouts.preview :role="$role" :roles="$previewRoles" active="reports" title="Pratinjau PDF Laporan Perkembangan">
    <x-preview.topbar title="Pratinjau PDF Laporan Perkembangan" :subtitle="'Pratinjau laporan '.$period.' untuk semua siswa sebelum diunduh sebagai PDF. Area tanda tangan disiapkan untuk tanda tangan basah.'" :profile="$profile" />
    @include('dev.gpk.pdf.styles')
    <nav class="abk-pdf-preview-toolbar" aria-label="Navigasi pratinjau PDF">
        <a class="abk-pdf-preview-back" href="{{ route('dev.gpk.reports.show', $laporan) }}">
            <span aria-hidden="true">&larr;</span> Kembali ke Detail Laporan
        </a>
        <a class="abk-primary-button abk-pdf-download-button" href="{{ route('dev.gpk.reports.pdf.download', $laporan) }}">Unduh PDF</a>
    </nav>
    <section class="abk-pdf-preview-stage" aria-label="Pratinjau dokumen PDF">
        <div class="abk-pdf-preview-pages">@include('dev.gpk.pdf.report-sheet')</div>
    </section>
</x-layouts.preview>
