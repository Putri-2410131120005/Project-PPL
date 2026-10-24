<x-layouts.preview :role="$role" :roles="$previewRoles" active="recaps" title="Pratinjau PDF Rekap Perkembangan">
    <x-preview.topbar title="Pratinjau PDF Laporan Perkembangan" :subtitle="'Pratinjau laporan '.$siswa->nama_siswa.' sebelum diunduh sebagai PDF. Area tanda tangan disiapkan untuk tanda tangan basah.'" :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span><a href="{{ route('dev.gpk.recaps.index') }}">Rekap</a><span>/</span><a href="{{ route('dev.gpk.recaps.show', ['siswa' => $siswa, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Detail {{ $siswa->nama_siswa }}</a><span>/</span>Pratinjau PDF</div>
    <div class="abk-gpk-page-actions"><a class="abk-dark-button" href="{{ route('dev.gpk.recaps.pdf.download', ['siswa' => $siswa, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Unduh PDF</a></div>
    @include('dev.gpk.pdf.styles')
    <section class="abk-pdf-preview-stage">@include('dev.gpk.pdf.recap-sheet')</section>
</x-layouts.preview>
