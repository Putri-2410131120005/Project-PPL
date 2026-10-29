<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" title="Detail Siswa ABK">
    <x-preview.topbar title="Detail Siswa ABK" subtitle="Profil, data wali, pendamping, dan perkembangan terverifikasi siswa." :profile="$profile" />
    <x-preview.breadcrumbs :home="route('dev.gpk.dashboard')" :items="[['Data Siswa', route('dev.gpk.students.index')], [$siswa->nama_siswa, null]]" />
    <section class="abk-panel abk-gpk-student-hero">
        <div class="abk-student-identity"><x-preview.student-avatar :student="$siswa" /><div><h2>{{ $siswa->nama_siswa }}</h2><p>{{ $siswa->kelas?->nama_kelas ?: 'Tanpa kelas' }} · NIS {{ $siswa->nis }}</p><span class="abk-need-pill">{{ $siswa->kebutuhan_khusus ?: 'Belum dicatat' }}</span></div></div>
        <div class="abk-gpk-student-meta">
            <div><small>GPK</small><strong>{{ $gpk->nama }}</strong></div>
            <div><small>Tim Inklusi / Pendamping</small><strong>{{ $siswa->relasiPendampingSiswa->filter(fn ($relasi) => $relasi->pendamping?->role?->nama_role === 'Tim Inklusi')->pluck('pendamping.nama')->filter()->join(', ') ?: '—' }}</strong></div>
            <div><small>Orang Tua / Wali</small><strong>{{ $siswa->relasiOrangTuaSiswa->map(fn ($relasi) => $relasi->orangTua?->pengguna?->nama)->filter()->join(', ') ?: '—' }}</strong></div>
        </div>
    </section>
    <h2 class="abk-section-heading">Perkembangan terverifikasi</h2>
    <p class="abk-section-description">Catatan yang disetujui GPK langsung tersedia bagi Orang Tua/Wali.</p>
    <section class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle">
        <thead><tr><th>Tanggal</th><th>Sumber</th><th>Ketersediaan</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($history as $item)<tr>
            <td>{{ $item['date']->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $item['source'] }}</td>
            <td>Tersedia untuk Orang Tua</td>
            <td><a class="abk-button text-decoration-none" href="{{ route('dev.gpk.notes.show', ['type'=>$item['type'],'id'=>$item['note']->getKey()]) }}">Lihat Catatan</a></td>
        </tr>@empty<tr><td colspan="4" class="abk-empty-cell">Belum ada perkembangan terverifikasi.</td></tr>@endforelse</tbody>
    </table></div></section>
    <x-preview.pagination :paginator="$history" />
</x-layouts.preview>
