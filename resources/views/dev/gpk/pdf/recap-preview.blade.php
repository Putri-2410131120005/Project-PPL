<x-layouts.preview :role="$role" :roles="$previewRoles" active="recaps" title="Pratinjau PDF Rekap Perkembangan">
    <x-preview.topbar title="Pratinjau PDF Rekap Perkembangan" :subtitle="'Pratinjau rekap '.$period.' untuk '.$siswa->nama_siswa.' sebelum diunduh sebagai PDF. Area tanda tangan disiapkan untuk tanda tangan basah.'" :profile="$profile" />
    @include('dev.gpk.pdf.styles')
    <nav class="abk-pdf-preview-toolbar" aria-label="Navigasi pratinjau PDF">
        <a class="abk-pdf-preview-back" href="{{ route('dev.gpk.recaps.show', ['siswa' => $siswa, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">
            <span aria-hidden="true">&larr;</span> Kembali ke Detail Rekap
        </a>
        <a class="abk-primary-button abk-pdf-download-button" href="{{ route('dev.gpk.recaps.pdf.download', ['siswa' => $siswa, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Unduh PDF</a>
    </nav>
    <section class="abk-pdf-preview-stage" aria-label="Pratinjau dokumen PDF">
        <div class="abk-pdf-preview-pages">@include('dev.gpk.pdf.recap-sheet')</div>
    </section>
</x-layouts.preview>
