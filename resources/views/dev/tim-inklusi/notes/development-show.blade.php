<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" title="Detail Catatan Guru Mata Pelajaran">
    <x-preview.topbar title="Detail Catatan Guru Mata Pelajaran" subtitle="Lihat catatan perkembangan siswa beserta sumber dan status verifikasinya." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.tim-inklusi.dashboard') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.tim-inklusi.students.show', $note->siswa) }}">Detail Siswa</a> <span>/</span> Detail Catatan</div>
    <section class="abk-panel abk-note-card">
        <div class="abk-note-heading"><div><h2>{{ $note->siswa->nama_siswa }}</h2><p>{{ $note->siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }}</p></div><x-preview.status-badge :status="$note->status_pengajuan" /></div>
        <div class="abk-note-meta"><div><small>Sumber Catatan</small><strong>Guru Mata Pelajaran</strong></div><div><small>Nama Guru</small><strong>{{ $note->pembuat?->nama ?: '—' }}</strong></div><div><small>Mata Pelajaran</small><strong>{{ $note->mataPelajaran?->nama_mapel ?: '—' }}</strong></div><div><small>Tanggal</small><strong>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d F Y') }}</strong></div></div>
        @foreach ([['Materi / Kegiatan', $note->materi_kegiatan], ['Kendala / Peristiwa', $note->kendala_peristiwa], ['Tindakan', $note->tindakan], ['Hasil Tindakan', $note->hasil_tindakan], ['Catatan Tambahan', $note->catatan_tambahan]] as [$label, $value])
            <div class="abk-readonly-field"><small>{{ $label }}</small><p>{{ $value ?: 'Tidak ada informasi yang dicatat.' }}</p></div>
        @endforeach
        <div class="abk-verification-box"><strong>Status verifikasi</strong><x-preview.status-badge class="mt-2" :status="$note->status_pengajuan" />@if ($latestVerification)<p>Diproses oleh {{ $latestVerification->verifikator?->nama ?: 'GPK' }} • {{ $latestVerification->tanggal_verifikasi->locale('id')->translatedFormat('d F Y') }}</p>@endif @if ($latestVerification?->catatan_verifikasi)<p>Catatan GPK: {{ $latestVerification->catatan_verifikasi }}</p>@endif</div>
    </section>
</x-layouts.preview>
