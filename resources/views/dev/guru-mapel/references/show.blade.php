@php($published = $note->verifikasi->where('versi_pengajuan', $note->versi_pengajuan)->whereNotNull('tanggal_publikasi')->first())
<x-layouts.preview :role="$role" :roles="$roles" active="students" title="Detail Catatan Referensi">
    <x-preview.topbar title="Detail Catatan Referensi" subtitle="Catatan yang sudah diverifikasi dan dipublikasikan GPK." :profile="$profile" />
    <x-preview.breadcrumbs :items="[['Data Siswa', route('dev.guru-mapel.students.index')], [$note->siswa->nama_siswa, route('dev.guru-mapel.students.show', $note->id_siswa)], ['Detail Catatan', null]]" />
    <section class="abk-panel abk-note-card">
        <div class="abk-note-heading"><div><h2>{{ $note->siswa->nama_siswa }}</h2><p>{{ $note->siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }}</p></div><x-preview.status-badge status="Terverifikasi" /></div>
        <div class="abk-note-meta"><div><small>Sumber Catatan</small><strong>{{ $source === 'perkembangan' ? 'Guru Mata Pelajaran' : 'Tim Inklusi' }}</strong></div><div><small>Nama Guru / Pembuat</small><strong>{{ $note->pembuat->nama }}</strong></div><div><small>Mata Pelajaran</small><strong>{{ $source === 'perkembangan' ? ($note->mataPelajaran?->nama_mapel ?: '—') : 'Pendampingan' }}</strong></div><div><small>Tanggal</small><strong>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d F Y') }}</strong></div></div>
        @if ($source === 'perkembangan')
            @foreach (['materi_kegiatan' => 'Materi / TP yang Berkaitan', 'kendala_peristiwa' => 'Peristiwa / Hasil Pengamatan', 'tindakan' => 'Tindakan / Penyesuaian Guru', 'hasil_tindakan' => 'Respons / Hasil Setelah Tindakan', 'catatan_tambahan' => 'Catatan Tambahan / Kendala'] as $field => $label)<div class="abk-readonly-field"><small>{{ $label }}</small><p>{{ $note->$field ?: 'Belum diisi.' }}</p></div>@endforeach
        @else
            @foreach (['kegiatan_pendampingan' => 'Kegiatan Pendampingan', 'kendala' => 'Kendala', 'tindak_lanjut' => 'Tindak Lanjut'] as $field => $label)<div class="abk-readonly-field"><small>{{ $label }}</small><p>{{ $note->$field ?: 'Belum diisi.' }}</p></div>@endforeach
        @endif
        <div class="abk-verification-box"><strong>Status verifikasi dan publikasi</strong><x-preview.status-badge class="mt-2" status="Terverifikasi" /><p>Terverifikasi oleh {{ $published?->verifikator?->nama ?: 'GPK' }} &bull; {{ $published?->tanggal_verifikasi?->locale('id')->translatedFormat('d F Y') ?: '—' }}</p><p>Dipublikasikan {{ $published?->tanggal_publikasi?->locale('id')->translatedFormat('d F Y H:i') ?: '—' }}</p></div>
    </section>
</x-layouts.preview>
