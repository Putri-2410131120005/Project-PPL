<x-guru.layout :profile="$profile" title="Dashboard Guru Mata Pelajaran" subtitle="Pantau siswa yang Anda ajar dan status catatan perkembangan dari satu tempat.">
<div class="gm-welcome">Selamat datang, {{ $profile['name'] }}</div>
<div class="gm-stats">@foreach($stats as $label=>$number)<article class="abk-stat-card">
<div class="abk-stat-icon {{ ['abk-icon-indigo','abk-icon-amber','abk-icon-teal'][$loop->index] }}" aria-hidden="true">•</div>
<div>
<div class="abk-stat-number">{{ $number }}</div>
<div class="abk-stat-label">{{ $label }}</div>
</div>
</article>@endforeach</div>
<h2 class="gm-section-title">Catatan perkembangan terbaru</h2>
<p class="gm-section-subtitle">Pantau status catatan yang Anda kirim ke GPK.</p>
@include('dev.guru-mapel.partials.notes-table')
</x-guru.layout>
