{{-- Isi halaman laporan perkembangan bulanan yang dicetak ke PDF. --}}
<article class="abk-pdf-sheet">
    <header class="abk-pdf-letterhead">
        <p class="abk-pdf-school">{{ $schoolName }}</p>
        <h1 class="abk-pdf-title">Laporan Perkembangan Siswa Berkebutuhan Khusus (ABK)</h1>
        <p class="abk-pdf-period">Periode {{ $period }}</p>
    </header>
    <table class="abk-pdf-info" aria-label="Informasi laporan">
        <tr><td class="abk-pdf-label">Jenis Laporan</td><td class="abk-pdf-separator">:</td><td>Laporan Perkembangan Siswa ABK</td></tr>
        <tr><td class="abk-pdf-label">Periode</td><td class="abk-pdf-separator">:</td><td>{{ $period }}</td></tr>
        <tr><td class="abk-pdf-label">Cakupan Siswa</td><td class="abk-pdf-separator">:</td><td>Semua Siswa</td></tr>
    </table>
    <table class="abk-pdf-table">
        <colgroup><col style="width:6%"><col style="width:21%"><col style="width:14%"><col style="width:18%"><col style="width:41%"></colgroup>
        <thead><tr><th>No.</th><th>Nama Siswa</th><th>Kelas</th><th>Jumlah Catatan</th><th>Ringkasan Perkembangan</th></tr></thead>
        <tbody>
        @forelse($students as $student)
            <tr>
                <td class="abk-pdf-center">{{ $loop->iteration }}</td>
                <td>{{ $student['nama_siswa'] }}</td>
                <td class="abk-pdf-center">{{ $student['kelas'] }}</td>
                <td class="abk-pdf-center">{{ $student['jumlah_catatan'] }} catatan</td>
                <td>{{ collect($student['ringkasan'])->filter()->join(' ') ?: 'Belum ada catatan terverifikasi pada bulan ini.' }}</td>
            </tr>
        @empty
            <tr><td class="abk-pdf-empty" colspan="5">Tidak ada data siswa dalam laporan ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    <section class="abk-pdf-approval">
        <p class="abk-pdf-date">Banjarmasin, {{ $printDate }}</p>
        @include('dev.gpk.pdf.signatures')
    </section>
    @include('dev.gpk.pdf.footer')
</article>
