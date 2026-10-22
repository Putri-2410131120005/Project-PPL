<x-principal.layout :profile="$profile" title="Pratinjau PDF Laporan Perkembangan" subtitle="Pratinjau file PDF resmi sesuai siswa dan periode yang dipilih." active="recaps" :detail="true">
<div class="ks-export-toolbar"><a class="ks-button ks-primary" data-download href="{{ preg_replace('/([?&])inline=1(&|$)/','$1',$pdfUrl) }}">Unduh PDF</a></div>
<p class="ks-section-description">{{ count($documents) }} laporan: {{ collect($documents)->map(fn ($d) => $d['student'].' · '.$d['period'])->unique()->implode('; ') }}</p>
<div class="ks-pdf-stage"><iframe src="{{ $pdfUrl }}" title="Pratinjau PDF laporan resmi" class="ks-pdf-frame"></iframe></div>
<a class="ks-button ks-outline ks-back" href="{{ $backUrl }}">Kembali</a></x-principal.layout>
