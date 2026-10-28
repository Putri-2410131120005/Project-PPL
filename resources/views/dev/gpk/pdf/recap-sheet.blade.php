<article class="abk-pdf-sheet">
    <p class="abk-pdf-school">{{ $schoolName }}</p>
    <h1 class="abk-pdf-title">LAPORAN PERKEMBANGAN SISWA ABK</h1>
    <p class="abk-pdf-period">{{ $period }}</p>
    <div class="abk-pdf-info">
        <p>Nama Siswa : {{ $siswa->nama_siswa }}</p>
        <p>Kelas : {{ $siswa->kelas?->nama_kelas ?: '—' }}</p>
    </div>
    <table class="abk-pdf-table">
        <colgroup><col style="width:13%"><col style="width:19%"><col style="width:32%"><col style="width:36%"></colgroup>
        <thead><tr><th>Tanggal</th><th>Sumber</th><th>Rekomendasi / Tindak Lanjut</th><th>Ringkasan Peristiwa</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr>
                <td class="abk-pdf-center">{{ $record['date']->locale('id')->translatedFormat('d M Y') }}</td>
                <td class="abk-pdf-center">{{ $record['source'] }}</td>
                <td>{{ $record['recommendation'] }}</td>
                <td>{{ $record['summary'] }}</td>
            </tr>
        @empty
            <tr><td class="abk-pdf-empty" colspan="4">Belum ada catatan terverifikasi pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p class="abk-pdf-date">Banjarmasin, {{ $printDate }}</p>
    @include('dev.gpk.pdf.signatures')
</article>
