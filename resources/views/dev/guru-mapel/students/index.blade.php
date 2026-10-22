<x-layouts.preview :role="$role" :roles="$roles" title="Data Siswa Guru Mata Pelajaran" active="students">
    <x-preview.topbar title="Data Siswa yang Diajar" subtitle="Daftar siswa ABK pada kelas yang Anda ajar."
        :profile="$profile" />

    <div class="abk-breadcrumb">Data Siswa</div>

    <section class="abk-panel p-3">
        <div class="d-flex justify-content-between
                    align-items-center flex-wrap gap-3 mb-3">
            <h2 class="h6 mb-0">Daftar Siswa</h2>
            <span class="badge text-bg-secondary">DATA DEMO</span>
        </div>

        <form method="GET" action="{{ route('dev.guru-mapel.students.index') }}" class="d-flex flex-wrap gap-2 mb-3">

            <input type="search" name="q" class="form-control" style="max-width: 300px" placeholder="Cari nama siswa..."
                value="{{ request('q') }}">

            <select name="tingkat" class="form-select" style="max-width: 160px">
                @foreach (['Semua', 'X', 'XI', 'XII'] as $tingkat)
                    <option value="{{ $tingkat }}" @selected(request('tingkat', 'Semua') === $tingkat)>
                        {{ $tingkat === 'Semua' ? 'Semua Kelas' : 'Kelas ' . $tingkat }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary">
                Cari / Filter
            </button>
        </form>

        <div class="table-responsive">
            <table class="table abk-table align-middle">
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
                    @forelse ($students as $student)
                        <tr>
                            <td class="abk-data-name">
                                {{ $student['nama'] }}
                            </td>
                            <td>{{ $student['kelas'] }}</td>
                            <td>{{ $student['jenis'] }}</td>
                            <td>{{ $student['pendamping'] }}</td>
                            <td>{{ $student['terakhir'] }}</td>
                            <td>
                                <a href="{{ route('dev.guru-mapel.students.show', ['slug' => $student['slug']]) }}"
                                    class="abk-button d-inline-block text-decoration-none">
                                    Lihat Profil
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                Tidak ada data siswa yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Menampilkan {{ $students->count() }} siswa demo.
        </p>
    </section>
</x-layouts.preview>