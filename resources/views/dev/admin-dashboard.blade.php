<x-layouts.preview :role="$role" :roles="$roles" title="Dashboard Admin / Pengelola">
    <x-preview.topbar title="Dashboard Admin / Pengelola" subtitle="Pantau ringkasan data utama dan buka menu pengelolaan sistem." :profile="$profile" />
    <div class="abk-breadcrumb">Dashboard</div>
    <div class="abk-welcome">Selamat datang, Admin / Pengelola <span class="abk-demo-tag">DATA DEMO</span></div>
    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card value="42" label="Total Pengguna" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card value="24" label="Guru" tone="teal" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card value="18" label="Siswa ABK" tone="amber" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card value="18" label="Orang Tua/Wali" tone="red" /></div>
    </div>
    <h2 class="abk-section-heading">Ringkasan data utama</h2>
    <p class="abk-section-description">Akses cepat ke data yang dikelola admin. Seluruh nilai adalah data demo.</p>
    <div class="abk-panel overflow-hidden"><div class="table-responsive"><table class="table abk-table align-middle"><thead><tr><th>Data</th><th>Jumlah</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
        @foreach ([['Pengguna', '42 akun', 'Akun seluruh pengguna sistem'], ['Guru', '24 guru', 'Data guru dan tim inklusi'], ['Siswa ABK', '18 siswa', 'Data siswa ABK terdaftar'], ['Orang Tua/Wali', '18 data', 'Data orang tua/wali terhubung']] as [$data, $jumlah, $keterangan])
            <tr><td class="abk-data-name">{{ $data }}</td><td>{{ $jumlah }}</td><td>{{ $keterangan }}</td><td><button type="button" class="abk-button" disabled>Kelola</button></td></tr>
        @endforeach
    </tbody></table></div></div>
</x-layouts.preview>
