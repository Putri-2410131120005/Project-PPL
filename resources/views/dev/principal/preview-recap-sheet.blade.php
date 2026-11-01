{{-- Menampilkan lembar rekap pertama pada area pratinjau PDF. --}}
@php($document = $documents[0])
<article class="abk-pdf-sheet">
    <header class="abk-pdf-letterhead">
        <p class="abk-pdf-school">SMAN 4 BANJARMASIN</p>
        <h1 class="abk-pdf-title">Laporan Perkembangan Siswa Berkebutuhan Khusus (ABK)</h1>
        <p class="abk-pdf-period">Periode {{ $document['period'] }}</p>
    </header>
    <table class="abk-pdf-info" aria-label="Informasi laporan">
        <tr><td class="abk-pdf-label">Jenis Laporan</td><td class="abk-pdf-separator">:</td><td>Laporan Perkembangan Siswa ABK</td></tr>
        <tr><td class="abk-pdf-label">Nama Siswa</td><td class="abk-pdf-separator">:</td><td>{{ $document['student'] }}</td></tr>
        <tr><td class="abk-pdf-label">Kelas</td><td class="abk-pdf-separator">:</td><td>{{ $document['class'] }}</td></tr>
        <tr><td class="abk-pdf-label">Periode</td><td class="abk-pdf-separator">:</td><td>{{ $document['period'] }}</td></tr>
    </table>
    <table class="abk-pdf-table">
        <colgroup><col style="width:6%"><col style="width:13%"><col style="width:18%"><col style="width:30%"><col style="width:33%"></colgroup>
        <thead><tr><th>No.</th><th>Tanggal</th><th>Sumber</th><th>Rekomendasi / Tindak Lanjut</th><th>Ringkasan Peristiwa</th></tr></thead>
        <tbody>
        @forelse($document['sources'] as $source)
            <tr>
                <td class="abk-pdf-center">{{ $loop->iteration }}</td>
                <td class="abk-pdf-center">{{ \Illuminate\Support\Carbon::parse($source['date'])->locale('id')->translatedFormat('d M Y') }}</td>
                <td class="abk-pdf-center">{{ $source['source'] }}@if($source['subject']) · {{ $source['subject'] }}@endif</td>
                <td>{{ $source['action'] }}</td>
                <td>{{ $source['result'] ?: $source['event'] }}</td>
            </tr>
        @empty
            <tr><td class="abk-pdf-empty" colspan="5">Belum ada catatan terverifikasi pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    @include('dev.principal.preview-signatures', ['document' => $document])
</article>
