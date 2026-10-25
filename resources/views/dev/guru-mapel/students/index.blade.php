<x-guru.layout :breadcrumbs="[['Data Siswa', null]]" :profile="$profile" title="Data Siswa yang Diajar" subtitle="Daftar siswa pada kelas yang Anda ajar." active="students">
<form class="gm-filters abk-panel" method="get">
<input name="q" type="search" placeholder="Cari nama siswa..." aria-label="Cari nama siswa" value="{{ request('q') }}">
@foreach(['Semua','X','XI','XII'] as $level)<button name="tingkat" value="{{ $level }}" class="gm-chip {{ request('tingkat','Semua') === $level ? 'active' : '' }}">{{ $level }}</button>@endforeach
<span class="gm-count">Total {{ $students->total() }} siswa terdaftar</span>
</form>
<section class="abk-panel gm-table">
<div class="table-responsive">
<table class="table abk-table gm-students-table">
<colgroup>
<col style="width:19.283%">
<col style="width:12.457%">
<col style="width:14.164%">
<col style="width:17.577%">
<col style="width:11.604%">
<col style="width:24.915%">
</colgroup>
<thead>
<tr>
<th>Nama Siswa</th>
<th>Kelas</th>
<th>Jenis Disabilitas</th>
<th>Guru Pendamping</th>
<th>Catatan Terakhir</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
@forelse($students as $student)<tr>
<td class="abk-data-name">{{ $student->nama_siswa }}</td>
<td>{{ $student->kelas->nama_kelas }}</td>
<td>
<span class="gm-pill">{{ $student->kebutuhan_khusus ?: 'Belum tersedia' }}</span>
</td>
<td>{{ $student->relasiPendampingSiswa->map->pendamping->filter(fn($p) => $p && $p->status_akun === 'Aktif' && in_array($p->role?->nama_role, ['Tim Inklusi','GPK']))->pluck('nama')->join(', ') ?: 'Belum tersedia' }}</td>
<td>{{ $student->terakhir ? \Illuminate\Support\Carbon::parse($student->terakhir)->format('d M Y') : 'Belum ada' }}</td>
<td>
<div class="gm-actions">
<a class="abk-button gm-profile-button" href="{{ route('dev.guru-mapel.students.show',$student->getKey()) }}">Lihat Profil</a>
<a class="gm-button gm-navy" href="{{ route('dev.guru-mapel.notes.create',$student->getKey()) }}">Tambah Catatan</a>
</div>
</td>
</tr>
@empty<tr>
<td colspan="6" class="gm-empty">Tidak ada data yang cocok. Coba pilihan lain atau <a href="{{ route('dev.guru-mapel.students.index') }}">tampilkan semua data</a>.</td>
</tr>@endforelse
</tbody>
</table>
</div>
</section>
<x-guru.pagination :paginator="$students" />
</x-guru.layout>
