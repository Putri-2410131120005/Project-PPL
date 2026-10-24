<article class="abk-pdf-sheet">
    <p class="abk-pdf-school">{{ $schoolName }}</p>
    <h1 class="abk-pdf-title">LAPORAN PERKEMBANGAN SISWA ABK</h1>
    <p class="abk-pdf-period">{{ $period }}</p>
    <div class="abk-pdf-info"><p>Jenis Laporan : Laporan Perkembangan Siswa ABK</p><p>Siswa : Semua Siswa</p></div>
    <table class="abk-pdf-table">
        <colgroup><col style="width:22%"><col style="width:15%"><col style="width:20%"><col style="width:43%"></colgroup>
        <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Jumlah Catatan</th><th>Ringkasan Perkembangan</th></tr></thead>
        <tbody>
        @forelse($students as $student)
            <tr>
                <td class="abk-pdf-center">{{ $student['nama_siswa'] }}</td>
                <td class="abk-pdf-center">{{ $student['kelas'] }}</td>
                <td class="abk-pdf-center">{{ $student['jumlah_catatan'] }} catatan</td>
                <td>{{ collect($student['ringkasan'])->filter()->join(' ') ?: 'Belum ada catatan terverifikasi pada bulan ini.' }}</td>
            </tr>
        @empty
            <tr><td class="abk-pdf-empty" colspan="4">Tidak ada data siswa dalam laporan ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p class="abk-pdf-date">Banjarmasin, {{ $printDate }}</p>
    @include('dev.gpk.pdf.signatures')
</article>
