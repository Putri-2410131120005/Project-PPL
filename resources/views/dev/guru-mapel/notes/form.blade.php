@php($editing = $note !== null)
@php($revision = $editing && ($note->status_pengajuan === 'Perlu Revisi' || $note->versi_pengajuan > 1))
<x-guru.layout :breadcrumbs="$editing ? [['Status Verifikasi', route('dev.guru-mapel.notes.status')], ['Detail Catatan - '.$student->nama_siswa, route('dev.guru-mapel.notes.show', $note->getKey())], [$revision ? 'Perbaiki Catatan' : 'Edit Draft Catatan', null]] : [['Data Siswa', route('dev.guru-mapel.students.index')], ['Detail Siswa - '.$student->nama_siswa, route('dev.guru-mapel.students.show', $student->getKey())], ['Tambah Catatan', null]]" :profile="$profile" :title="$revision ? 'Perbaiki Catatan Perkembangan' : ($editing ? 'Edit Draft Catatan' : 'Tambah Catatan Perkembangan')" subtitle="Catat kondisi pembelajaran secara terstruktur: peristiwa, tindakan guru, dan respons siswa." :active="$editing ? 'verification' : 'students'" :detail="true">
<form method="post" action="{{ $editing ? route('dev.guru-mapel.notes.update',$note->getKey()) : route('dev.guru-mapel.notes.store',$student->getKey()) }}" class="abk-panel gm-card gm-note-form">@csrf
@unless($editing)<input type="hidden" name="creation_token" value="{{ session('guru_note_creation_token') }}">@endunless
@if($editing)@method('PUT')<input type="hidden" name="revision_token" value="{{ hash('sha256',json_encode($note->getRawOriginal())) }}">@endif
<div class="gm-card-heading">
<div>
<h2>{{ $student->nama_siswa }}</h2>
<p>{{ $student->kelas->nama_kelas }}</p>
</div>
<x-guru.status :status="$note?->status_pengajuan ?? 'Draft'" />
</div>
<div class="gm-meta">
<div>
<small>Sumber Catatan</small>
<strong>Guru Mata Pelajaran</strong>
</div>
<div>
<label for="id_mapel">Mata Pelajaran</label>@if($editing)<strong>{{ $note->mataPelajaran->nama_mapel }}</strong>@else<select id="id_mapel" name="id_mapel" required>
<option value="">Pilih mata pelajaran</option>@foreach($subjects as $assignment)<option value="{{ $assignment->id_mapel }}" @selected(old('id_mapel') == $assignment->id_mapel)>{{ $assignment->mataPelajaran->nama_mapel }}</option>@endforeach</select>@endif</div>
<div>
<small>Nama Guru</small>
<strong>{{ $profile['name'] }}</strong>
</div>
<div>
<label for="tanggal_catatan">Tanggal</label>
<input id="tanggal_catatan" name="tanggal_catatan" type="date" value="{{ old('tanggal_catatan',$note?->tanggal_catatan?->format('Y-m-d') ?? now()->toDateString()) }}" required>
</div>
</div>
@if($revision)@php($reason = $note->verifikasi->where('status_verifikasi','Perlu Revisi')->sortByDesc('versi_pengajuan')->first())<div class="gm-revision">
<strong>Catatan Perbaikan dari GPK</strong>
<p>{{ $reason?->catatan_verifikasi }}</p>
</div>@endif
@include('dev.guru-mapel.partials.fields',['editable'=>true])
<div class="gm-form-actions">
<strong>Simpan atau kirim catatan</strong>
<div class="gm-actions">
<button name="aksi" value="draft" class="gm-button gm-teal">{{ $revision ? 'Simpan Perbaikan' : 'Simpan Draft' }}</button>
<button name="aksi" value="kirim" class="gm-button gm-primary">{{ $revision ? 'Kirim Ulang untuk Verifikasi' : 'Kirim ke GPK' }}</button>
<a class="gm-button gm-outline" href="{{ $editing ? route('dev.guru-mapel.notes.show',$note->getKey()) : route('dev.guru-mapel.students.show',$student->getKey()) }}">Kembali</a>
</div>
</div>
@if($subjects->isEmpty())<p class="gm-errors">Tidak ada mata pelajaran aktif. Penyimpanan tidak diizinkan.</p>@endif
</form>
</x-guru.layout>
