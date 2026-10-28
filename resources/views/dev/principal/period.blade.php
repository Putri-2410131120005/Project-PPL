<x-principal.layout :breadcrumbs="[['Arsip Laporan', route('dev.principal.archives')], ['Detail Laporan Periode - '.$period, null]]" :profile="$profile" :title="'Laporan per Siswa — '.$period" subtitle="Pantau ringkasan resmi siswa dari laporan bulanan yang telah dipublikasikan GPK." active="archives" :detail="true">
    <x-principal.exports :available="$reports->total() > 0" />

    <form method="GET" class="abk-panel ks-meta abk-report-period-meta">
        <input type="hidden" name="periode" value="{{ $period }}">
        @foreach (request()->only(['bulan', 'tahun', 'q', 'tingkat', 'tahun_ajaran']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
        <div><small>Periode</small><strong>{{ $period }}</strong></div>
        <div><small>Jenis Laporan</small><strong>Laporan Perkembangan Siswa ABK</strong></div>
        <div><label for="selected-student">Siswa</label><select id="selected-student" name="siswa" data-auto-submit><option value="">Semua Siswa</option>@foreach ($students as $student)<option value="{{ $student->getKey() }}" @selected((string) request('siswa') === (string) $student->getKey())>{{ $student->nama_siswa }}</option>@endforeach</select></div>
        <noscript><button class="ks-button ks-primary">Terapkan</button></noscript>
    </form>

    @if ($selectedStudent)
        <x-reports.student-summary :student="$selectedStudent" :close-url="route('dev.principal.period', ['periode' => $period] + request()->except('siswa'))" viewer="principal" />
    @endif

    <section class="abk-panel ks-table">
        <div class="abk-report-table-heading"><div><h2>Daftar ringkasan siswa</h2><p>Kepala Sekolah hanya melihat laporan yang sudah dipublikasikan GPK.</p></div></div>
        <div class="table-responsive"><table class="table abk-table">
            <thead><tr><th>Nama Siswa</th><th>Kelas Saat Ini</th><th>Catatan Terverifikasi</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($reports as $report)
                <tr>
                    <td class="abk-data-name">{{ $report->rekap->siswa->nama_siswa }}</td>
                    <td>{{ $report->rekap->siswa->kelas->nama_kelas }}</td>
                    <td>{{ $report->rekap->sumberCatatan->count() }} catatan</td>
                    <td><a class="ks-button ks-secondary ks-summary-button" href="{{ route('dev.principal.period', ['periode' => $period, 'siswa' => $report->rekap->siswa->getKey()] + request()->except(['periode', 'siswa', 'page'])) }}">Lihat Ringkasan</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="ks-empty">Tidak ada laporan siswa yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <x-principal.paging :data="$reports" />
</x-principal.layout>
