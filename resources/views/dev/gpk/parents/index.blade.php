<x-layouts.preview :role="$role" :roles="$previewRoles" active="parents" title="Data Orang Tua / Wali">
    <x-preview.topbar title="Data Orang Tua / Wali" subtitle="Data wali siswa dalam penugasan GPK; halaman ini bersifat baca saja." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span>Data Orang Tua / Wali</div>
    <section class="abk-panel abk-inclusion-toolbar">
        <form class="abk-search-form" method="GET">@if($filters['tingkat'])<input type="hidden" name="tingkat" value="{{ $filters['tingkat'] }}">@endif<input class="form-control abk-search-input" name="cari" value="{{ $filters['cari'] }}" placeholder="Cari nama siswa..."></form>
        <nav class="abk-chip-list">@foreach([''=>'Semua','X'=>'X','XI'=>'XI','XII'=>'XII'] as $value=>$label)<a class="abk-filter {{ $filters['tingkat']===$value?'active':'' }}" href="{{ route('dev.gpk.parents.index',array_filter(['cari'=>$filters['cari'],'tingkat'=>$value])) }}">{{ $label }}</a>@endforeach</nav>
        <div class="abk-users-count">Total {{ $relations->total() }} relasi wali</div>
    </section>
    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle">
        <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Orang Tua / Wali</th><th>Hubungan</th><th>No. Telepon</th><th>Alamat</th></tr></thead>
        <tbody>@forelse($relations as $relation)<tr><td class="abk-data-name">{{ $relation->siswa?->nama_siswa ?: '—' }}</td><td>{{ $relation->siswa?->kelas?->nama_kelas ?: '—' }}</td><td>{{ $relation->orangTua?->pengguna?->nama ?: '—' }}</td><td>{{ $relation->hubungan ?: '—' }}</td><td>{{ $relation->orangTua?->no_telepon ?: '—' }}</td><td>{{ $relation->orangTua?->alamat ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="abk-empty-cell">Belum ada data wali untuk siswa dalam penugasan ini.</td></tr>@endforelse</tbody>
    </table></div></section><x-preview.pagination :paginator="$relations" />
</x-layouts.preview>
