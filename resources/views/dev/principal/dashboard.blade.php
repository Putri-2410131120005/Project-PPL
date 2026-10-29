<x-principal.layout :profile="$profile" title="Dashboard Kepala Sekolah" subtitle="Lihat ringkasan monitoring dan laporan perkembangan siswa ABK dari satu tempat.">
<div class="ks-welcome">Selamat datang, Kepala Sekolah</div>
<div class="ks-stats abk-stat-grid abk-stat-grid-3">
<a class="abk-stat-card" href="{{ route('dev.principal.students') }}"><span class="abk-stat-icon abk-icon-indigo" aria-hidden="true">•</span><div><div class="abk-stat-number">{{ $stats['students'] }}</div><div class="abk-stat-label">Siswa Terdaftar</div></div></a>
<div class="abk-stat-card"><span class="abk-stat-icon abk-icon-amber" aria-hidden="true">•</span><div><div class="abk-stat-number">{{ $stats['verified'] }}</div><div class="abk-stat-label">Catatan Terverifikasi</div></div></div>
<a class="abk-stat-card" href="{{ route('dev.principal.archives') }}"><span class="abk-stat-icon abk-icon-red" aria-hidden="true">•</span><div><div class="ks-stat-period">{{ $stats['period'] ?? 'Belum tersedia' }}</div><div class="abk-stat-label">Laporan Terbaru</div></div></a>
</div>
<h2 class="ks-section-title">{{ $stats['period'] ? 'Laporan '.$stats['period'] : 'Laporan Perkembangan' }}</h2><p class="ks-section-description">Ringkasan laporan resmi yang sudah dipublikasikan GPK.</p>
<section class="abk-panel ks-table"><div class="table-responsive"><table class="table abk-table"><thead><tr><th>Jumlah Siswa</th><th>Catatan Dipublikasikan</th><th>Diperbarui</th><th>Aksi</th></tr></thead><tbody>
@if ($archive)<tr><td>{{ $archive->student_count }} siswa</td><td>{{ $archive->note_count }} catatan</td><td>{{ \Illuminate\Support\Carbon::parse($archive->updated_at)->format('d M Y') }}</td><td><a class="ks-button ks-navy" href="{{ route('dev.principal.period', ['periode' => $archive->periode]) }}">Lihat Laporan</a></td></tr>
@else<tr><td colspan="4" class="ks-empty">Belum ada laporan resmi yang dipublikasikan GPK.</td></tr>@endif
</tbody></table></div></section>
</x-principal.layout>
