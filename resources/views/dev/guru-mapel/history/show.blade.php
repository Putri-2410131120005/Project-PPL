<x-guru.layout :profile="$profile" title="Detail Riwayat Perkembangan" subtitle="Catatan yang sudah dipublikasikan GPK." active="history" :detail="true">
<section class="abk-panel gm-card">
<div class="gm-card-heading">
<div>
<h2>{{ $note->siswa->nama_siswa }}</h2>
<p>{{ $note->siswa->kelas->nama_kelas }} (kelas saat ini) &middot; {{ $note->tanggal_catatan->format('d M Y') }}</p>
</div>
<x-guru.status status="Terverifikasi" />
</div>
<div class="gm-meta">
<div>
<small>Sumber Catatan</small>
<strong>{{ $source === 'perkembangan' ? 'Guru Mata Pelajaran' : 'Tim Inklusi' }}</strong>
</div>
<div>
<small>Nama Guru / Pembuat</small>
<strong>{{ $note->pembuat->nama }}</strong>
</div>
<div><small>Mata Pelajaran</small><strong>{{ $source === 'perkembangan' ? $note->mataPelajaran?->nama_mapel : 'Pendampingan' }}</strong></div>
<div><small>Tanggal</small><strong>{{ $note->tanggal_catatan->format('d M Y') }}</strong></div></div>
@if($source === 'perkembangan')@include('dev.guru-mapel.partials.fields')@else @foreach(['kegiatan_pendampingan'=>'Kegiatan Pendampingan','kendala'=>'Kendala','tindak_lanjut'=>'Tindak Lanjut'] as $field=>$label)<div class="gm-field">
<label>{{ $label }}</label>
<div class="gm-value">{{ $note->$field ?: 'Belum diisi' }}</div>
</div>@endforeach @endif
@php($published = $note->verifikasi->where('versi_pengajuan', $note->versi_pengajuan)->whereNotNull('tanggal_publikasi')->first())
<div class="gm-form-actions"><strong>Status verifikasi dan publikasi</strong><p>Terverifikasi oleh {{ $published?->verifikator?->nama }} &middot; {{ $published?->tanggal_verifikasi?->format('d M Y') }}</p><p>Dipublikasikan {{ $published?->tanggal_publikasi?->format('d M Y H:i') }}</p></div>
<a class="gm-button gm-outline mt-3" href="{{ route('dev.guru-mapel.history.index') }}">Kembali</a>
</section>
</x-guru.layout>
