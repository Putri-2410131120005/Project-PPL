<article class="abk-pdf-sheet">
    <header class="abk-pdf-letterhead">
        <p class="abk-pdf-school">{{ $schoolName }}</p>
        <h1 class="abk-pdf-title">Laporan Perkembangan Siswa Berkebutuhan Khusus (ABK)</h1>
        <p class="abk-pdf-period">Periode {{ $period }}</p>
    </header>
    <table class="abk-pdf-info" aria-label="Informasi laporan">
        <tr><td class="abk-pdf-label">Jenis Laporan</td><td class="abk-pdf-separator">:</td><td>Laporan Perkembangan Siswa ABK</td></tr>
        <tr><td class="abk-pdf-label">Nama Siswa</td><td class="abk-pdf-separator">:</td><td>{{ $siswa->nama_siswa }}</td></tr>
        <tr><td class="abk-pdf-label">Kelas</td><td class="abk-pdf-separator">:</td><td>{{ $siswa->kelas?->nama_kelas ?: '—' }}</td></tr>
        <tr><td class="abk-pdf-label">Periode</td><td class="abk-pdf-separator">:</td><td>{{ $period }}</td></tr>
    </table>
    <table class="abk-pdf-table">
        <colgroup><col style="width:6%"><col style="width:13%"><col style="width:18%"><col style="width:30%"><col style="width:33%"></colgroup>
        <thead><tr><th>No.</th><th>Tanggal</th><th>Sumber</th><th>Rekomendasi / Tindak Lanjut</th><th>Ringkasan Peristiwa</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr>
                <td class="abk-pdf-center">{{ $loop->iteration }}</td>
                <td class="abk-pdf-center">{{ $record['date']->locale('id')->translatedFormat('d M Y') }}</td>
                <td class="abk-pdf-center">{{ $record['source'] }}</td>
                <td>{{ $record['recommendation'] }}</td>
                <td>{{ $record['summary'] }}</td>
            </tr>
        @empty
            <tr><td class="abk-pdf-empty" colspan="5">Belum ada catatan terverifikasi pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    <section class="abk-pdf-approval">
        <p class="abk-pdf-date">Banjarmasin, {{ $printDate }}</p>
        @include('dev.gpk.pdf.signatures')
    </section>
    @include('dev.gpk.pdf.footer')
</article>
