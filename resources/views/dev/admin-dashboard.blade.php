<x-layouts.preview :role="$role" :roles="$roles" title="Dashboard Admin / Pengelola" active="dashboard">
    <x-preview.topbar title="Dashboard Admin / Pengelola" subtitle="Ringkasan data sekolah dan akses cepat ke seluruh pengelolaan SMART-ABK." :profile="$profile" />
    <div class="abk-breadcrumb">Dashboard</div>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card :value="$dashboardStats['users']" label="Total Pengguna" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card :value="$dashboardStats['staff']" label="Data Guru" tone="teal" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card :value="$dashboardStats['students']" label="Siswa ABK" tone="amber" /></div>
        <div class="col-12 col-md-6 col-xl-3"><x-preview.stat-card :value="$dashboardStats['parents']" label="Orang Tua/Wali" tone="red" /></div>
    </div>

    <h2 class="abk-section-heading">Kelola data sekolah</h2>
    <p class="abk-section-description">Semua halaman administrasi tersedia dari dashboard ini.</p>

    @php
        $sections = [
            ['Master Pengguna', $dashboardStats['users'], 'Kelola seluruh akun dan statusnya, termasuk Orang Tua/Wali.', route('dev.admin.users.index')],
            ['Siswa ABK', $dashboardStats['students'], 'Data induk siswa, NIS, kelas, kebutuhan khusus, dan status.', route('dev.admin.students.index')],
            ['Kelas', $dashboardStats['classes'], 'Struktur kelas dan tahun ajaran.', route('dev.admin.classes.index')],
            ['Mata Pelajaran', $dashboardStats['subjects'], 'Master mata pelajaran sekolah.', route('dev.admin.subjects.index')],
            ['Penugasan Guru', $dashboardStats['guruMappings'], 'Hubungan guru, kelas, dan mata pelajaran.', route('dev.admin.mappings.guru-mapel-kelas.index')],
            ['Pendamping Siswa', $dashboardStats['pendampingMappings'], 'Hubungan siswa ABK dan pendamping.', route('dev.admin.mappings.pendamping-siswa.index')],
            ['Orang Tua Siswa', $dashboardStats['parentMappings'], 'Hubungan siswa ABK dan Orang Tua/Wali.', route('dev.admin.mappings.orang-tua-siswa.index')],
        ];
    @endphp
    <section class="abk-panel overflow-hidden">
        <div class="table-responsive"><table class="table abk-table align-middle">
            <thead><tr><th>Bagian</th><th>Jumlah</th><th>Keterangan</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach ($sections as [$section, $count, $description, $url])
                    <tr>
                        <td class="abk-data-name">{{ $section }}</td>
                        <td>{{ $count ?? '-' }}</td>
                        <td>{{ $description }}</td>
                        <td><a class="abk-button text-decoration-none" href="{{ $url }}">Kelola</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </section>
</x-layouts.preview>
