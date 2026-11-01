<x-principal.layout :breadcrumbs="[['Rekap', route('dev.principal.recaps', ['semester' => $semester, 'tahun_ajaran' => $schoolYear])], ['Detail Rekap - '.$student->nama_siswa, null]]" :profile="$profile" :title="'Detail Rekap Perkembangan — '.$student->nama_siswa" subtitle="Riwayat perkembangan siswa selama satu semester berdasarkan catatan yang telah diverifikasi GPK." active="recaps" :detail="true">
    <div class="abk-gpk-page-actions abk-export-actions">
        <a class="abk-outline-button" data-download href="{{ route('dev.principal.verified-recap.download', ['siswa' => $student, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Unduh PDF</a>
        <a class="abk-dark-button" href="{{ route('dev.principal.verified-recap.preview', ['siswa' => $student, 'semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Pratinjau PDF</a>
    </div>
    <section class="abk-panel ks-meta">
        <div><small>Periode</small><strong>{{ $period }}</strong></div>
        <div><small>Nama Siswa</small><strong>{{ $student->nama_siswa }}</strong></div>
        <div><small>NIS</small><strong>{{ $student->nis }}</strong></div>
        <div><small>Kelas</small><strong>{{ $student->kelas?->nama_kelas ?: '—' }}</strong></div>
        <div><small>Jumlah Catatan</small><strong>{{ $sources->count() }} catatan</strong></div>
    </section>
    <section class="abk-panel ks-table">
        <div class="table-responsive"><table class="table abk-table align-middle mb-0">
            <thead><tr><th>Tanggal</th><th>Sumber / Pencatat</th><th>Catatan Perkembangan</th><th>Tindakan / Tindak Lanjut</th><th>Hasil / Kendala</th></tr></thead>
            <tbody>
            @forelse ($sources as $source)
                <tr>
                    <td>{{ $source['date']->locale('id')->translatedFormat('d M Y') }}</td>
                    <td>{{ $source['source'] }}@if($source['subject']) — {{ $source['subject'] }}@endif<br><strong>{{ $source['teacher'] }}</strong></td>
                    <td>{{ $source['event'] ?: $source['material'] ?: 'Tidak dicantumkan' }}</td>
                    <td>{{ $source['action'] ?: 'Tidak dicantumkan' }}</td>
                    <td>{{ $source['result'] ?: $source['extra'] ?: 'Tidak dicantumkan' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="abk-empty-cell">Belum ada catatan yang diverifikasi GPK pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <a class="ks-button ks-outline ks-back" href="{{ route('dev.principal.recaps', ['semester' => $semester, 'tahun_ajaran' => $schoolYear]) }}">Kembali</a>
</x-principal.layout>
