<x-layouts.preview :role="$role" :roles="$roles" title="Detail Siswa Guru Mata Pelajaran" active="students">
    <x-preview.topbar title="Detail Siswa"
        subtitle="Profil ringkas dan riwayat perkembangan sebagai referensi pembelajaran." :profile="$profile" />

    <div class="abk-breadcrumb mb-3">
        <a href="{{ route('dev.guru-mapel.students.index') }}">
            Data Siswa
        </a>
        / Detail Siswa
    </div>

    <section class="abk-panel p-4 mb-4">

        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h2 class="h4 fw-bold">
                    {{ $student['nama'] }}
                </h2>

                <p class="text-muted">
                    {{ $student['kelas'] }}
                </p>

                <span class="badge text-bg-secondary">
                    DATA DEMO
                </span>
            </div>

            <a href="{{ route('dev.guru-mapel.students.index') }}" class="btn btn-outline-secondary">
                Kembali
            </a>
        </div>

        <hr>

        <div class="row g-4">

            <div class="col-md-6">
                <p class="text-muted small mb-1">Nama Siswa</p>
                <strong>{{ $student['nama'] }}</strong>
            </div>

            <div class="col-md-6">
                <p class="text-muted small mb-1">Kelas</p>
                <strong>{{ $student['kelas'] }}</strong>
            </div>

            <div class="col-md-6">
                <p class="text-muted small mb-1">Jenis Disabilitas</p>
                <strong>{{ $student['jenis'] }}</strong>
            </div>

            <div class="col-md-6">
                <p class="text-muted small mb-1">Guru Pendamping</p>
                <strong>{{ $student['pendamping'] }}</strong>
            </div>

        </div>

        <hr>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('dev.guru-mapel.notes.create', ['slug' => request()->route('slug')]) }}"
                class="btn btn-primary">
                Tambah Catatan
            </a>

            <a href="{{ route('dev.guru-mapel.recommendations.show', ['slug' => request()->route('slug')]) }}"
                class="btn btn-outline-primary">
                Lihat Rekomendasi
            </a>
        </div>

    </section>

    <section class="abk-panel p-4">
        <h3 class="h5 fw-bold">
            Riwayat Perkembangan
        </h3>

        <p class="text-muted mb-0">
            Belum ada riwayat pada data demo ini.
        </p>
    </section>

</x-layouts.preview>