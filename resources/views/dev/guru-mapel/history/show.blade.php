
<x-layouts.preview
    :role="$role"
    :roles="$roles"
    title="Detail Riwayat Perkembangan"
    active="history"
>
    <x-preview.topbar
        title="Detail Riwayat Perkembangan"
        subtitle="Informasi riwayat perkembangan siswa sebagai referensi pembelajaran."
        :profile="$profile"
    />

    <div class="abk-breadcrumb mb-3">
        <a href="{{ route('dev.guru-mapel.history.index') }}">
            Riwayat Perkembangan
        </a>
        / Detail Riwayat
    </div>

    <section class="abk-panel p-4">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">

            <div>
                <h2 class="h5 fw-bold mb-2">
                    {{ $riwayat['nama'] }}
                </h2>

                <p class="text-muted mb-2">
                    {{ $riwayat['kelas'] }}
                </p>

                <span class="badge text-bg-secondary">
                    DATA DEMO
                </span>
            </div>

            <a
                href="{{ route('dev.guru-mapel.history.index') }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>

        </div>

        <h3 class="h6 fw-bold mb-3">
            Informasi Catatan Perkembangan
        </h3>

        <div class="row g-3 mb-4">

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Nama Siswa
                </label>

                <input
                    type="text"
                    class="form-control"
                    value="{{ $riwayat['nama'] }}"
                    readonly
                >
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Kelas
                </label>

                <input
                    type="text"
                    class="form-control"
                    value="{{ $riwayat['kelas'] }}"
                    readonly
                >
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Sumber Catatan
                </label>

                <input
                    type="text"
                    class="form-control"
                    value="{{ $riwayat['sumber'] }}"
                    readonly
                >
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Tanggal Catatan
                </label>

                <input
                    type="text"
                    class="form-control"
                    value="{{ $riwayat['tanggal'] }}"
                    readonly
                >
            </div>

        </div>

        <hr>

        <h3 class="h6 fw-bold mb-3">
            Catatan Perkembangan Siswa
        </h3>

        <div class="mb-4">
            <label class="form-label fw-semibold">
                Hasil Pengamatan / Perkembangan
            </label>

            <textarea
                class="form-control"
                rows="5"
                readonly
            >{{ $riwayat['isi'] }}</textarea>
        </div>

        <div class="alert alert-info mb-0">
            <strong>Informasi Riwayat</strong>

            <p class="small mt-2 mb-0">
                Halaman ini menampilkan contoh riwayat
                perkembangan siswa sebagai referensi
                pembelajaran Guru Mata Pelajaran.
                Data yang digunakan masih berupa demo.
            </p>
        </div>

    </section>
</x-layouts.preview>
