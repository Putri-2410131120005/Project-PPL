{{-- Dokumen HTML utama untuk PDF rekap perkembangan GPK. --}}
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Rekap Perkembangan {{ $siswa->nama_siswa }}</title>@include('dev.gpk.pdf.styles')</head><body class="abk-pdf-document">@include('dev.gpk.pdf.recap-sheet')</body></html>
