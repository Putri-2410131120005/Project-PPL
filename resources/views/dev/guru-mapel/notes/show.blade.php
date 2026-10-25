<x-guru.layout :breadcrumbs="[['Status Verifikasi', route('dev.guru-mapel.notes.status')], ['Detail Catatan - '.$note->siswa->nama_siswa.' - '.$note->tanggal_catatan->format('d M Y'), null]]" :profile="$profile" title="Detail Catatan Perkembangan" subtitle="Lihat peristiwa, tindakan guru, dan respons siswa." active="verification" :detail="true">
<section class="abk-panel gm-card">
<div class="gm-card-heading">
<div>
<h2>{{ $note->siswa->nama_siswa }}</h2>
<p>{{ $note->siswa->kelas->nama_kelas }} &middot; Versi {{ $note->versi_pengajuan }}</p>
</div>
<x-guru.status :status="$note->status_pengajuan" />
</div>
<div class="gm-meta">
<div>
<small>Sumber Catatan</small>
<strong>Guru Mata Pelajaran</strong>
</div>
<div>
<small>Mata Pelajaran</small>
<strong>{{ $note->mataPelajaran->nama_mapel }}</strong>
</div>
<div>
<small>Nama Guru</small>
<strong>{{ $note->pembuat->nama }}</strong>
</div>
<div>
<small>Tanggal</small>
<strong>{{ $note->tanggal_catatan->format('d M Y') }}</strong>
</div>
</div>
@if($note->status_pengajuan === 'Draft')<div class="gm-notice">Draft Tersimpan{{ $note->versi_pengajuan > 1 ? ' - Draft Perbaikan' : '' }}</div>@endif
@if($note->tanggal_kirim)<p>Dikirim {{ $note->tanggal_kirim->format('d M Y H:i') }}</p>@endif
@include('dev.guru-mapel.partials.fields')
@include('dev.guru-mapel.partials.decisions')
<div class="gm-actions">
<a class="gm-button gm-outline" href="{{ route('dev.guru-mapel.notes.status') }}">Kembali</a>@if(in_array($note->status_pengajuan,['Draft','Perlu Revisi']))<a class="gm-button gm-primary" href="{{ route('dev.guru-mapel.notes.revise',$note->getKey()) }}">{{ $note->status_pengajuan === 'Draft' ? 'Lanjut Edit' : 'Perbaiki Catatan' }}</a>@endif</div>
</section>
</x-guru.layout>
