@php($returned = $note->status_pengajuan === 'Perlu Revisi')
<x-layouts.preview :role="$role" :roles="$previewRoles" active="history" :title="$returned ? 'Detail Catatan — Dikembalikan untuk Revisi' : 'Detail Catatan Pendampingan'">
    <x-preview.topbar :title="$returned ? 'Detail Catatan — Dikembalikan untuk Revisi' : 'Detail Catatan Pendampingan'" :subtitle="$returned ? 'Catatan dikembalikan oleh GPK dan dapat diperbaiki lalu dikirim ulang untuk verifikasi.' : 'Lihat hasil pendampingan, tindak lanjut, dan kebutuhan dukungan siswa.'" :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.tim-inklusi.dashboard') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.tim-inklusi.notes.index') }}">Riwayat Catatan</a> <span>/</span> Detail Catatan Pendampingan</div>
    <x-preview.flash-messages />

    <section class="abk-panel abk-note-card">
        <div class="abk-note-heading"><div><h2>{{ $note->siswa->nama_siswa }}</h2><p>{{ $note->siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }}</p></div><x-preview.status-badge :status="$note->status_pengajuan" /></div>
        <div class="abk-note-meta"><div><small>Sumber Catatan</small><strong>Tim Inklusi / Guru Pendamping</strong></div><div><small>Nama Pendamping</small><strong>{{ $pendamping->nama }}</strong></div><div><small>Tanggal</small><strong>{{ $note->tanggal_catatan->locale('id')->translatedFormat('d F Y') }}</strong></div></div>

        @if ($returned && $latestVerification?->catatan_verifikasi)<div class="abk-revision-note"><strong>Catatan Perbaikan dari GPK</strong><p>{{ $latestVerification->catatan_verifikasi }}</p></div>@endif
        <div class="abk-readonly-field"><small>Catatan Pendampingan</small><p>{{ $note->kegiatan_pendampingan }}</p></div>
        <div class="abk-readonly-field"><small>Tindak Lanjut Pendampingan</small><p>{{ $note->tindak_lanjut ?: 'Tidak ada tindak lanjut yang dicatat.' }}</p></div>
        <div class="abk-readonly-field"><small>Kendala / Kebutuhan Dukungan</small><p>{{ $note->kendala ?: 'Tidak ada kendala yang dicatat.' }}</p></div>
        <div class="abk-verification-box"><strong>Status verifikasi</strong><x-preview.status-badge class="mt-2" :status="$note->status_pengajuan" />
            @if ($latestVerification)<p>Diproses oleh {{ $latestVerification->verifikator?->nama ?: 'GPK' }} • {{ $latestVerification->tanggal_verifikasi->locale('id')->translatedFormat('d F Y') }}</p>@elseif ($note->status_pengajuan === 'Dikirim')<p>Catatan sedang menunggu pemeriksaan GPK.</p>@else<p>Catatan belum dikirim untuk verifikasi.</p>@endif
            @if ($latestVerification?->catatan_verifikasi && ! $returned)<p>Catatan GPK: {{ $latestVerification->catatan_verifikasi }}</p>@endif
            @if (in_array($note->status_pengajuan, ['Draft', 'Perlu Revisi'], true))<a class="abk-teal-button mt-2" href="{{ route('dev.tim-inklusi.notes.edit', $note) }}">{{ $returned ? 'Perbaiki Catatan' : 'Edit Draft' }}</a>@endif
        </div>
    </section>
</x-layouts.preview>
