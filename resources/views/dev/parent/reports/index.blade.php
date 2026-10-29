<x-layouts.preview :role="$role" :roles="$previewRoles" active="reports" :title="$student ? 'Laporan Perkembangan '.$student->nama_siswa : 'Laporan Perkembangan Anak'">
    <x-preview.topbar :title="$student ? 'Laporan Perkembangan '.$student->nama_siswa : 'Laporan Perkembangan Anak'" subtitle="Ringkasan perkembangan semester anak yang telah dipublikasikan GPK." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.parent.dashboard')" :items="[['Laporan Perkembangan', null]]" />

    <section class="abk-panel abk-parent-report-filters">
        <form method="GET" action="{{ route('dev.parent.reports.index') }}">
            <label><span>Semester</span><select class="form-select" name="semester" onchange="this.form.submit()"><option value="Ganjil" @selected($semester === 'Ganjil')>Ganjil</option><option value="Genap" @selected($semester === 'Genap')>Genap</option></select></label>
            <label><span>Periode</span><select class="form-select" name="periode" onchange="this.form.submit()"><option value="">Semua periode</option>@foreach ($periodOptions as $option)<option value="{{ $option }}" @selected($period === $option)>{{ $option }}</option>@endforeach</select></label>
        </form>
        <div><small>Siswa</small><strong>{{ $student?->nama_siswa ?: 'Belum terhubung' }}</strong></div>
    </section>

    @if ($report)
        <section class="abk-panel abk-parent-report-summary"><small>Tanggal laporan • {{ $report->tanggal_publikasi->locale('id')->translatedFormat('d F Y') }}</small><h2>{{ $report->periode }}</h2><p>{{ $report->isi_laporan }}</p></section>
        <section class="abk-panel overflow-hidden abk-parent-report-table"><div class="table-responsive"><table class="table abk-table align-middle"><thead><tr><th>Tanggal</th><th>Sumber</th><th>Rekomendasi / Tindak Lanjut</th><th>Ringkasan Peristiwa</th></tr></thead><tbody>
            @forelse ($sources as $source)
                <tr><td>{{ $source['date']->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $source['source'] }}@if ($source['author'])<small class="d-block text-muted mt-1">{{ $source['author'] }}</small>@endif</td><td>{{ $source['recommendation'] ?: '—' }}</td><td>{{ $source['summary'] }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada sumber catatan yang dapat ditampilkan untuk laporan ini.</td></tr>
            @endforelse
        </tbody></table></div></section>
    @else
        <section class="abk-panel abk-parent-report-empty"><h2>Belum ada laporan untuk semester ini</h2><p>Laporan perkembangan {{ $student?->nama_siswa ?: 'anak Anda' }} untuk {{ $periodLabel }} belum dipublikasikan oleh GPK.</p><p>Pilih semester atau periode lain untuk melihat laporan yang tersedia.</p>@if ($semester === 'Genap')<a class="abk-primary-button" href="{{ route('dev.parent.reports.index', ['semester' => 'Ganjil']) }}">Lihat Laporan Ganjil</a>@endif</section>
    @endif
    <p class="abk-parent-report-note">Laporan ini bersifat informatif. Orang tua hanya dapat melihat laporan perkembangan anak yang terhubung.</p>
</x-layouts.preview>
