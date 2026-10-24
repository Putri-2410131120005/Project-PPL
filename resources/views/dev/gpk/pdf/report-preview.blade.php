<x-layouts.preview :role="$role" :roles="$previewRoles" active="reports" title="Pratinjau PDF Laporan Perkembangan">
    <x-preview.topbar title="Pratinjau PDF Laporan Perkembangan" :subtitle="'Pratinjau laporan '.$period.' untuk semua siswa sebelum diunduh sebagai PDF. Area tanda tangan disiapkan untuk tanda tangan basah.'" :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span><a href="{{ route('dev.gpk.reports.index') }}">Laporan</a><span>/</span><a href="{{ route('dev.gpk.reports.show', $laporan) }}">Detail {{ $period }}</a><span>/</span>Pratinjau PDF</div>
    <div class="abk-gpk-page-actions"><a class="abk-dark-button" href="{{ route('dev.gpk.reports.pdf.download', $laporan) }}">Unduh PDF</a></div>
    @include('dev.gpk.pdf.styles')
    <section class="abk-pdf-preview-stage">@include('dev.gpk.pdf.report-sheet')</section>
</x-layouts.preview>
