<section class="abk-panel gm-table">
<div class="table-responsive">
<table class="table abk-table gm-notes-table">
<colgroup>
<col style="width:28.157%">
<col style="width:13.823%">
<col style="width:16.468%">
<col style="width:17.406%">
<col style="width:24.146%">
</colgroup>
<thead>
<tr>
<th>Nama Siswa</th>
<th>Kelas</th>
<th>Tanggal</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($notes as $note)<tr>
<td class="abk-data-name">{{ $note->siswa->nama_siswa }}</td>
<td>{{ $note->siswa->kelas->nama_kelas }}</td>
<td>{{ $note->tanggal_catatan->format('d M Y') }}</td>
<td>
<x-guru.status :status="$note->status_pengajuan" />
</td>
<td>
<a class="abk-button" href="{{ route('dev.guru-mapel.notes.show',$note->getKey()) }}">Detail Catatan</a>@if(in_array($note->status_pengajuan,['Draft','Perlu Revisi'])) <a class="gm-button gm-navy" href="{{ route('dev.guru-mapel.notes.revise',$note->getKey()) }}">{{ $note->status_pengajuan === 'Draft' ? 'Edit Draft' : 'Perbaiki Catatan' }}</a>@endif</td>
</tr>@empty<tr>
<td colspan="5" class="gm-empty">Tidak ada catatan yang cocok.</td>
</tr>@endforelse</tbody>
</table>
</div>
</section>
