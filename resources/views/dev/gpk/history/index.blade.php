<x-layouts.preview :role="$role" :roles="$previewRoles" active="history" title="Riwayat Verifikasi">
    <x-preview.topbar title="Riwayat Verifikasi" subtitle="Arsip keputusan seluruh GPK untuk setiap versi catatan." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.gpk.dashboard') }}">Dashboard</a><span>/</span>Riwayat Verifikasi</div>
    <section class="abk-panel abk-gpk-filter-panel">
        <form method="GET" class="abk-gpk-filter-form">
            <input class="form-control abk-search-input" name="cari" value="{{ $filters['search'] }}" placeholder="Cari nama siswa...">
            <select class="form-select" name="sumber"><option value="">Semua Sumber</option><option value="perkembangan" @selected($filters['source']==='perkembangan')>Guru Mata Pelajaran</option><option value="pendampingan" @selected($filters['source']==='pendampingan')>Tim Inklusi / Pendamping</option></select>
            <button class="abk-dark-button" type="submit">Terapkan</button><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.history.index') }}">Reset</a>
        </form><div class="abk-users-count">Total {{ $history->total() }} keputusan</div>
    </section>
    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle">
        <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Versi</th><th>Sumber</th><th>Keputusan / Rekomendasi</th><th>GPK & Waktu</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($history as $verification)
            @php
                $type = $verification->id_catatan_perkembangan ? 'perkembangan' : 'pendampingan';
                $note = $type === 'perkembangan' ? $verification->catatanPerkembangan : $verification->catatanPendampingan;
                $source = $type === 'perkembangan' ? 'Guru Mapel · '.($note?->pembuat?->nama ?: '—').' · '.($note?->mataPelajaran?->nama_mapel ?: '—') : 'Tim Inklusi · '.($note?->pembuat?->nama ?: '—');
            @endphp
            <tr>
                <td class="abk-data-name">{{ $note?->siswa?->nama_siswa ?: '—' }}</td><td>{{ $note?->siswa?->kelas?->nama_kelas ?: '—' }}</td><td>v{{ $verification->versi_pengajuan }}</td>
                <td>{{ $source }}</td><td><x-preview.status-badge :status="$verification->status_verifikasi" />@if($verification->catatan_verifikasi)<small class="d-block mt-1 text-muted">{{ $verification->catatan_verifikasi }}</small>@endif</td>
                <td>{{ $verification->verifikator?->nama ?: '—' }}<small class="d-block text-muted">{{ $verification->verifikator?->jabatan_gpk ?: 'GPK' }} · {{ $verification->tanggal_verifikasi?->locale('id')->translatedFormat('d M Y H:i') }}</small></td>
                <td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.notes.show',['type'=>$type,'id'=>$note->getKey()]) }}">Lihat Catatan</a></td>
            </tr>
        @empty<tr><td colspan="7" class="abk-empty-cell">Belum ada riwayat verifikasi yang sesuai.</td></tr>@endforelse</tbody>
    </table></div></section>
    <x-preview.pagination :paginator="$history" />
</x-layouts.preview>
