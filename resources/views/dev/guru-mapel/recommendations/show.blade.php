
<x-layouts.preview
    :role="$role"
    :roles="$roles"
    title="Rekomendasi Pembelajaran"
    active="students"
>
    <x-preview.topbar
        title="Rekomendasi Pembelajaran"
        subtitle="Informasi penyesuaian pembelajaran berdasarkan kebutuhan siswa."
        :profile="$profile"
    />

    <div class="abk-breadcrumb mb-3">
        <a href="{{ route('dev.guru-mapel.students.index') }}">
            Data Siswa
        </a>
        /
        <a href="{{ route('dev.guru-mapel.students.show', ['slug' => $slug]) }}">
            Detail Siswa
        </a>
        / Rekomendasi
    </div>

    <section class="abk-panel p-4">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-2">
                    {{ $student['nama'] }}
                </h2>

                <p class="text-muted mb-2">
                    {{ $student['kelas'] }}
                </p>

                <span class="badge text-bg-secondary">
                    DATA DEMO
                </span>
            </div>

            <a
                href="{{ route('dev.guru-mapel.students.show', ['slug' => $slug]) }}"
                class="btn btn-outline-secondary"
            >
                Kembali
            </a>
        </div>

        <h3 class="h6 fw-bold mb-3">
            Daftar Rekomendasi Pembelajaran
        </h3>

        <div class="row g-3">
            @foreach ($student['rekomendasi'] as $item)
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <h4 class="h6 fw-bold mb-2">
                            {{ $item['judul'] }}
                        </h4>

                        <p class="text-muted mb-0">
                            {{ $item['isi'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="alert alert-info mt-4 mb-0">
            <strong>Informasi Rekomendasi</strong>

            <p class="small mt-2 mb-0">
                Rekomendasi pada halaman ini merupakan
                contoh untuk keperluan pengembangan.
                Data sebenarnya akan dihubungkan
                ke sistem SMART-ABK pada tahap integrasi.
            </p>
        </div>

    </section>
</x-layouts.preview>
