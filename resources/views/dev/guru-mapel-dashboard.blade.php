<x-layouts.preview :role="$role" :roles="$roles" title="Dashboard Guru Mata Pelajaran">
    <x-preview.topbar title="Dashboard Guru Mata Pelajaran" subtitle="Pantau siswa yang Anda ajar dan status catatan perkembangan dari satu tempat." :profile="$profile" />
    <div class="abk-breadcrumb">Dashboard</div>
    <div class="abk-welcome">Selamat datang, Guru Mata Pelajaran <span class="abk-demo-tag">DATA DEMO</span></div>
    <div class="row g-3"><div class="col-12 col-md-4"><x-preview.stat-card value="3" label="Siswa ABK yang Diajar" /></div><div class="col-12 col-md-4"><x-preview.stat-card value="2" label="Menunggu Verifikasi" tone="amber" /></div><div class="col-12 col-md-4"><x-preview.stat-card value="5" label="Terverifikasi" tone="teal" /></div></div>
    <h2 class="abk-section-heading">Catatan perkembangan terbaru</h2><p class="abk-section-description">Pantau status catatan yang Anda kirim ke GPK. Ini hanya tampilan data demo.</p>
    <div class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle"><thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <tr><td class="abk-data-name">Aulia Rahman</td><td>XI IPA 1</td><td>14 Sep 2026</td><td><span class="abk-status abk-status-pending">Menunggu Verifikasi</span></td><td><button class="abk-button" disabled>Lihat Catatan</button></td></tr>
        <tr><td class="abk-data-name">Rizky Maulana</td><td>XI IPA 2</td><td>13 Sep 2026</td><td><span class="abk-status abk-status-verified">Terverifikasi</span></td><td><button class="abk-button" disabled>Lihat Catatan</button></td></tr>
    </tbody></table></div></div>
</x-layouts.preview>
