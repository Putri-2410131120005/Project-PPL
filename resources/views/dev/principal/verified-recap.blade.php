<x-principal.layout :breadcrumbs="[['Rekap', route('dev.principal.recaps')], ['Catatan Terverifikasi - '.$student->nama_siswa, null]]" :profile="$profile" :title="'Catatan Terverifikasi — '.$student->nama_siswa" subtitle="Catatan perkembangan dan pendampingan yang telah disetujui GPK." active="recaps" :detail="true">
    <section class="abk-panel ks-meta">
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
                <tr><td colspan="5" class="abk-empty-cell">Belum ada catatan yang diverifikasi GPK.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>

    <a class="ks-button ks-outline ks-back" href="{{ route('dev.principal.recaps') }}">Kembali</a>
</x-principal.layout>
