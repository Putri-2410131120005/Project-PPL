@php($latestDecision = $note->verifikasi->sortByDesc('versi_pengajuan')->first())
<x-layouts.preview :role="$role" :roles="$roles" active="history" title="Detail Catatan Perkembangan">
    <x-preview.topbar title="Detail Catatan Perkembangan" subtitle="Lihat peristiwa, tindakan guru, dan respons siswa." :profile="$profile" />
    <x-preview.breadcrumbs :items="[['Riwayat Catatan', route('dev.guru-mapel.notes.status')], ['Detail Catatan', null]]" />
    <x-preview.flash-messages />
    <section class="abk-panel abk-note-card">
        <div class="abk-note-heading"><div><h2>{{ $note->siswa->nama_siswa }}</h2><p>{{ $note->siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }} &bull; Versi {{ $note->versi_pengajuan }}</p></div><x-preview.status-badge :status="$note->status_pengajuan" /></div>
        <div class="abk-note-meta"><div><small>Sumber Catatan</small><strong>Guru Mata Pelajaran</strong></div><div><small>Mata Pelajaran</small><strong>{{ $note->mataPelajaran->nama_mapel }}</strong></div><div><small>Nama Guru</small><strong>{{ $note->pembuat->nama }}</strong></div><div><small>Tanggal</small><strong>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d F Y') }}</strong></div></div>
        @if ($note->status_pengajuan === 'Draft')<div class="alert alert-info">Draft Tersimpan{{ $note->versi_pengajuan > 1 ? ' — Draft Perbaikan' : '' }}</div>@endif
        @if ($note->status_pengajuan === 'Perlu Revisi' && $latestDecision?->catatan_verifikasi)<div class="abk-revision-note"><strong>Catatan Perbaikan dari GPK</strong><p>{{ $latestDecision->catatan_verifikasi }}</p></div>@endif
        @foreach (['materi_kegiatan' => 'Materi / TP yang Berkaitan', 'kendala_peristiwa' => 'Peristiwa / Hasil Pengamatan', 'tindakan' => 'Tindakan / Penyesuaian Guru', 'hasil_tindakan' => 'Respons / Hasil Setelah Tindakan', 'catatan_tambahan' => 'Catatan Tambahan / Kendala'] as $field => $label)<div class="abk-readonly-field"><small>{{ $label }}</small><p>{{ $note->$field ?: 'Belum diisi.' }}</p></div>@endforeach
        @if ($note->verifikasi->isNotEmpty())
            <div class="abk-readonly-field"><small>Riwayat Keputusan GPK</small>
                @foreach ($note->verifikasi->sortByDesc('versi_pengajuan') as $decision)
                    <p><strong>Versi {{ $decision->versi_pengajuan }} &bull; {{ $decision->status_verifikasi }}</strong><br>{{ $decision->catatan_verifikasi ?: 'Tidak ada catatan tambahan.' }}<br>{{ $decision->verifikator?->nama ?: 'GPK' }} &bull; {{ $decision->tanggal_verifikasi?->locale('id')->translatedFormat('d F Y H:i') }} &bull; {{ $decision->tanggal_publikasi ? 'Dipublikasikan '.$decision->tanggal_publikasi->locale('id')->translatedFormat('d F Y H:i') : 'Belum dipublikasikan' }}</p>
                @endforeach
            </div>
        @endif
        <div class="abk-verification-box"><strong>Status verifikasi</strong><x-preview.status-badge class="mt-2" :status="$note->status_pengajuan" />
            @if ($latestDecision)<p>Diproses oleh {{ $latestDecision->verifikator?->nama ?: 'GPK' }} &bull; {{ $latestDecision->tanggal_verifikasi?->locale('id')->translatedFormat('d F Y') }}</p>@elseif ($note->status_pengajuan === 'Dikirim')<p>Catatan sedang menunggu pemeriksaan GPK.</p>@else<p>Catatan belum dikirim untuk verifikasi.</p>@endif
            @if (in_array($note->status_pengajuan, ['Draft', 'Perlu Revisi'], true))<a class="abk-teal-button mt-2" href="{{ route('dev.guru-mapel.notes.revise', $note) }}">{{ $note->status_pengajuan === 'Draft' ? 'Edit Draft' : 'Perbaiki Catatan' }}</a>@endif
        </div>
    </section>
</x-layouts.preview>
