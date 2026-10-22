
<x-layouts.preview
    :role="$role"
    :roles="$roles"
    title="Perbaiki Catatan Perkembangan"
    active="verification"
>
    <x-preview.topbar
        title="Perbaiki Catatan Perkembangan"
        subtitle="Perbaiki isi catatan berdasarkan masukan GPK."
        :profile="$profile"
    />

    <div class="abk-breadcrumb mb-3">
        <a href="{{ route('dev.guru-mapel.notes.status') }}">
            Status Verifikasi
        </a>
        / Perbaiki Catatan
    </div>

    <section class="abk-panel p-4">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h2 class="h5 fw-bold">
                    {{ $catatan['nama'] }}
                </h2>

                <p class="text-muted">
                    {{ $catatan['kelas'] }}
                </p>

                <span class="badge text-bg-secondary">
                    DATA DEMO
                </span>

                <span class="badge text-bg-danger">
                    Dikembalikan
                </span>
            </div>

            <a href="{{ route('dev.guru-mapel.notes.show', ['id' => $id]) }}"
               class="btn btn-outline-secondary">
                Kembali
            </a>
        </div>

        <div class="alert alert-warning">
            <strong>Catatan Perbaikan dari GPK</strong>

            <p class="mb-0 mt-2">
                {{ $catatan['alasan_gpk'] }}
            </p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Mata Pelajaran
                </label>
                <input
                    class="form-control"
                    value="{{ $catatan['mapel'] }}"
                    readonly
                >
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Tanggal Catatan
                </label>
                <input
                    class="form-control"
                    value="{{ $catatan['tanggal'] }}"
                    readonly
                >
            </div>
        </div>

        @foreach ([
            'Materi / Tujuan Pembelajaran' => 'materi',
            'Peristiwa / Hasil Pengamatan' => 'peristiwa',
            'Tindakan / Penyesuaian Guru' => 'tindakan',
            'Respons / Hasil Setelah Tindakan' => 'hasil',
            'Catatan Tambahan' => 'tambahan',
        ] as $label => $field)

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    {{ $label }}
                </label>

                <textarea
                    name="{{ $field }}"
                    class="form-control"
                    rows="3"
                >{{ $catatan[$field] }}</textarea>
            </div>

        @endforeach

        <hr>

        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-outline-primary" disabled>
                Simpan Draft Perbaikan
            </button>

            <button class="btn btn-primary" disabled>
                Kirim Ulang ke GPK
            </button>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Mode preview: perubahan belum disimpan
            ke database SMART-ABK.
        </p>

    </section>
</x-layouts.preview>
