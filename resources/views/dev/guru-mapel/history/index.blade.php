<x-guru.layout :breadcrumbs="[['Riwayat Perkembangan', null]]" :profile="$profile" title="Riwayat Perkembangan Siswa" subtitle="Lihat riwayat yang dipublikasikan GPK sebagai referensi pembelajaran." active="history">
<form method="get" class="gm-filters abk-panel">
<input type="search" name="q" aria-label="Cari nama siswa" placeholder="Cari nama siswa..." value="{{ request('q') }}">@if(request('siswa'))<input type="hidden" name="siswa" value="{{ request('siswa') }}">@endif @foreach(['Semua'=>'Semua','perkembangan'=>'Guru Mata Pelajaran','pendampingan'=>'Tim Inklusi'] as $value=>$label)<button class="gm-chip {{ request('sumber','Semua') === $value ? 'active' : '' }}" name="sumber" value="{{ $value }}">{{ $label }}</button>@endforeach<span class="gm-count">Total {{ $history->total() }} catatan</span>
</form>
<section class="abk-panel gm-table">
<div class="table-responsive">
<table class="table abk-table">
<thead>
<tr>
<th>Nama Siswa</th>
<th>Kelas Saat Ini</th>
<th>Tanggal</th>
<th>Sumber Catatan</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($history as $item)@php($note=$item['note'])<tr>
<td class="abk-data-name">{{ $note->siswa->nama_siswa }}</td>
<td>{{ $note->siswa->kelas->nama_kelas }}</td>
<td>{{ $note->tanggal_catatan->format('d M Y') }}</td>
<td>{{ $item['source'] === 'perkembangan' ? 'Guru Mata Pelajaran' : 'Tim Inklusi' }}</td>
<td>
<a class="abk-button" href="{{ route('dev.guru-mapel.history.show',['source'=>$item['source'],'id'=>$note->getKey()]) }}">Detail Riwayat</a>
</td>
</tr>@empty<tr>
<td colspan="5" class="gm-empty">Belum ada riwayat yang dapat ditampilkan.</td>
</tr>@endforelse</tbody>
</table>
</div>
</section>
<x-guru.pagination :paginator="$history" />
</x-guru.layout>
