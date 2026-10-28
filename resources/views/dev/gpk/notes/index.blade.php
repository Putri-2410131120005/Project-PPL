<x-layouts.preview :role="$role" :roles="$previewRoles" active="notes" title="Verifikasi Catatan">
    <x-preview.topbar title="Verifikasi Catatan" subtitle="Antrean seluruh catatan Guru Mata Pelajaran dan Tim Inklusi yang perlu ditinjau, dikembalikan, atau diverifikasi GPK." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span>Verifikasi Catatan</div>
    <section class="abk-panel abk-gpk-filter-panel">
        <form method="GET" class="abk-gpk-filter-form">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['search'] }}" placeholder="Cari nama siswa...">
            <select class="form-select" name="status">
                <option value="">Semua Status</option><option value="menunggu" @selected($filters['status']==='menunggu')>Menunggu Verifikasi</option>
                <option value="Terverifikasi" @selected($filters['status']==='Terverifikasi')>Terverifikasi</option><option value="Perlu Revisi" @selected($filters['status']==='Perlu Revisi')>Perlu Revisi</option>
            </select>
            <select class="form-select" name="sumber">
                <option value="">Sumber: Semua</option><option value="perkembangan" @selected($filters['source']==='perkembangan')>Guru Mata Pelajaran</option>
                <option value="pendampingan" @selected($filters['source']==='pendampingan')>Tim Inklusi / Pendamping</option>
            </select>
            <button class="abk-dark-button" type="submit">Terapkan</button>
            <a class="abk-button text-decoration-none" href="{{ route('dev.gpk.notes.index') }}">Reset</a>
        </form>
        <div class="abk-users-count">Total {{ $notes->total() }} catatan</div>
    </section>
    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle">
        <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Tanggal</th><th>Sumber Catatan</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($notes as $item)<tr>
            <td class="abk-data-name">{{ $item['student']->nama_siswa }}</td><td>{{ $item['student']->kelas?->nama_kelas ?: '—' }}</td>
            <td>{{ $item['date']->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $item['source'] }}</td><td><x-preview.status-badge :status="$item['status']" /></td>
            <td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.notes.show',['type'=>$item['type'],'id'=>$item['note']->getKey()]) }}">{{ $item['is_pending'] ? 'Tinjau' : 'Lihat Catatan' }}</a></td>
        </tr>@empty<tr><td colspan="6" class="abk-empty-cell">Tidak ada catatan yang sesuai dengan filter.</td></tr>@endforelse</tbody>
    </table></div></section>
    <x-preview.pagination :paginator="$notes" />
</x-layouts.preview>
