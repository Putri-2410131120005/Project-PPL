<x-guru.layout :profile="$profile" title="Rekomendasi Pembelajaran" subtitle="Lihat rekomendasi pembelajaran siswa sebagai acuan penyesuaian kegiatan belajar." active="students" :detail="true">
@include('dev.guru-mapel.partials.student-profile')
<section class="abk-panel gm-card">
<p role="status">Rekomendasi resmi belum tersedia. Sumber rekomendasi dan persetujuan GPK belum tersedia pada database.</p>
<div class="gm-recommendations">@foreach(['Penyampaian Instruksi','Dukungan Visual','Latihan dan Penguatan','Pemantauan Respons'] as $category)<article>
<h3>{{ $category }}</h3>
<p>Belum tersedia.</p>
</article>@endforeach</div>
</section>
<a class="gm-button gm-outline" href="{{ route('dev.guru-mapel.students.show',$student->getKey()) }}">Kembali</a>
</x-guru.layout>
