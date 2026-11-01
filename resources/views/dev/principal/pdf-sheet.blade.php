<article class="ks-pdf-page-sheet">
    <header class="ks-pdf-letterhead">
        <p class="ks-pdf-school">SMAN 4 BANJARMASIN</p>
        <h1 class="ks-pdf-title">Laporan Perkembangan Siswa Berkebutuhan Khusus (ABK)</h1>
        <p class="ks-pdf-period">Periode {{ $document['period'] }}</p>
    </header>
    <table class="ks-pdf-identity" aria-label="Informasi laporan">
        <tr><td class="ks-pdf-label">Jenis Laporan</td><td class="ks-pdf-separator">:</td><td>Laporan Perkembangan Siswa ABK</td></tr>
        <tr><td class="ks-pdf-label">Nama Siswa</td><td class="ks-pdf-separator">:</td><td>{{ $document['student'] }}</td></tr>
        <tr><td class="ks-pdf-label">NIS</td><td class="ks-pdf-separator">:</td><td>{{ $document['nis'] }}</td></tr>
        <tr><td class="ks-pdf-label">Kelas</td><td class="ks-pdf-separator">:</td><td>{{ $document['class'] }}</td></tr>
        <tr><td class="ks-pdf-label">Periode</td><td class="ks-pdf-separator">:</td><td>{{ $document['period'] }}</td></tr>
        <tr><td class="ks-pdf-label">Penyusun</td><td class="ks-pdf-separator">:</td><td>{{ $document['author'] }}</td></tr>
    </table>
    <h2>Ringkasan Perkembangan</h2>
    <p class="ks-pdf-narrative">{{ $document['summary'] }}</p>
    @if($document['report'])
        <h2>Isi Laporan</h2>
        <p class="ks-pdf-narrative">{{ $document['report'] }}</p>
    @endif
    <h2>Sumber Catatan Perkembangan</h2>
    <table class="ks-pdf-report-table">
        <colgroup><col style="width:6%"><col style="width:13%"><col style="width:23%"><col style="width:28%"><col style="width:30%"></colgroup>
        <thead><tr><th>No.</th><th>Tanggal</th><th>Sumber / Pencatat</th><th>Tindakan / Tindak Lanjut</th><th>Peristiwa / Hasil / Kendala</th></tr></thead>
        <tbody>
        @forelse($document['sources'] as $source)
            <tr><td class="ks-pdf-center">{{ $loop->iteration }}</td><td class="ks-pdf-center">{{ $source['date'] }}</td><td>{{ $source['source'] }}@if($source['subject']) — {{ $source['subject'] }}@endif<br>{{ $source['teacher'] }}</td><td>{{ $source['action'] }}</td><td>{{ $source['result'] ?: $source['event'] }}</td></tr>
        @empty
            <tr><td class="ks-pdf-empty" colspan="5">Tidak ada catatan sumber yang dicantumkan.</td></tr>
        @endforelse
        </tbody>
    </table>
    @foreach($document['sources'] as $source)
        <section class="ks-pdf-note">
            <h3>Catatan Lengkap {{ $loop->iteration }} — {{ $source['date'] }}</h3>
            <p><strong>Sumber:</strong> {{ $source['source'] }}@if($source['subject']) — {{ $source['subject'] }}@endif — {{ $source['teacher'] }}</p>
            @foreach(['material'=>'Materi','event'=>'Peristiwa / Kegiatan','action'=>'Tindakan / Tindak Lanjut','result'=>'Hasil / Kendala','extra'=>'Catatan Tambahan'] as $field=>$label)
                @if($source[$field])<p><strong>{{ $label }}:</strong> {{ $source[$field] }}</p>@endif
            @endforeach
        </section>
    @endforeach
    <section class="ks-pdf-approval">
        <p class="ks-pdf-place-date">Banjarmasin, {{ $document['printed_date'] }}</p>
        <table class="ks-pdf-signatures">
            <tr><td>Mengetahui,<br>Kepala Sekolah</td><td>Guru Pembimbing Khusus (GPK)</td></tr>
            <tr><td class="ks-pdf-signature-space"></td><td class="ks-pdf-signature-space"></td></tr>
            <tr><td><strong>{{ $document['headmaster'] ?: '(Nama Kepala Sekolah)' }}</strong><small>NIP. {{ $document['headmaster_nip'] ?: '________________' }}</small></td><td><strong>{{ $document['author'] }}</strong><small>NIP. {{ $document['author_nip'] ?: '________________' }}</small></td></tr>
        </table>
    </section>
    <footer class="ks-pdf-footer">SMART-ABK | SMAN 4 BANJARMASIN <span class="ks-pdf-page-number">Halaman </span></footer>
</article>
