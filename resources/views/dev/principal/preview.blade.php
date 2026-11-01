@php($archivePreview = request()->routeIs('dev.principal.period.preview'))
@php($previewDocument = $documents[0])
<x-principal.layout
    :profile="$profile"
    :title="$archivePreview ? 'Pratinjau PDF Laporan Perkembangan' : 'Pratinjau PDF Rekap Perkembangan'"
    :subtitle="$archivePreview
        ? 'Pratinjau laporan '.request('periode').' untuk semua siswa sebelum diunduh sebagai PDF. Area tanda tangan disiapkan untuk tanda tangan basah.'
        : 'Pratinjau rekap '.$previewDocument['period'].' untuk '.$previewDocument['student'].' sebelum diunduh sebagai PDF. Area tanda tangan disiapkan untuk tanda tangan basah.'"
    :active="$archivePreview ? 'archives' : 'recaps'"
    :detail="true"
    :show-breadcrumbs="false"
>
    @include('dev.gpk.pdf.styles')
    <nav class="abk-pdf-preview-toolbar" aria-label="Navigasi pratinjau PDF">
        <a class="abk-pdf-preview-back" href="{{ $backUrl }}">
            <span aria-hidden="true">&larr;</span> {{ $archivePreview ? 'Kembali ke Detail Laporan' : 'Kembali ke Detail Rekap' }}
        </a>
        <a class="abk-primary-button abk-pdf-download-button" data-download href="{{ preg_replace('/([?&])inline=1(&|$)/', '$1', $pdfUrl) }}">Unduh PDF</a>
    </nav>
    <section class="abk-pdf-preview-stage" aria-label="Pratinjau dokumen PDF">
        <div class="abk-pdf-preview-pages">
            @if($archivePreview)
                @include('dev.principal.preview-report-sheet')
            @else
                @include('dev.principal.preview-recap-sheet')
            @endif
        </div>
    </section>
</x-principal.layout>
