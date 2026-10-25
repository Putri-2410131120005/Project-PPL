
<x-layouts.preview
    :role="$role"
    :roles="$roles"
    title="Tambah Catatan Perkembangan"
    active="students"
>
    <x-preview.topbar
        title="Tambah Catatan Perkembangan"
        subtitle="Catat kondisi pembelajaran secara terstruktur: peristiwa, tindakan guru, dan respons siswa."
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
        / Tambah Catatan
    </div>

    <section class="abk-panel p-4 mb-4">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">
                    {{ $student['nama'] }}
                </h2>

                <p class="text-muted mb-2">
                    {{ $student['kelas'] }}
                </p>

                <span class="badge text-bg-secondary">
                    DATA DEMO — DRAFT
                </span>
            </div>

            <a href="{{ route('dev.guru-mapel.students.show', ['slug' => $slug]) }}"
               class="btn btn-outline-secondary">
                Kembali
            </a>
        </div>

        <div class="row g-3 mb-4">

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Sumber Catatan
                </label>
                <input type="text"
                       class="form-control"
                       value="Guru Mata Pelajaran"
                       readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Mata Pelajaran
                </label>
                <input type="text"
                       class="form-control"
                       value="Matematika (Demo)"
                       readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Nama Guru
                </label>
                <input type="text"
                       class="form-control"
                       value="Guru Mata Pelajaran (Demo)"
                       readonly>
            </div>

            <div class="col-md-6">
                <label for="tanggal" class="form-label fw-semibold">
                    Tanggal Catatan
                </label>
                <input id="tanggal"
                       type="date"
                       name="tanggal_catatan"
                       class="form-control"
                       value="{{ now()->toDateString() }}">
            </div>

        </div>

        <hr class="my-4">

        <div class="mb-3">
            <label for="materi" class="form-label fw-semibold">
                Materi / Tujuan Pembelajaran
            </label>
            <textarea id="materi"
                      name="materi_kegiatan"
                      class="form-control"
                      rows="3"
                      placeholder="Tuliskan materi atau tujuan pembelajaran..."></textarea>
        </div>

        <div class="mb-3">
            <label for="peristiwa" class="form-label fw-semibold">
                Peristiwa / Hasil Pengamatan
            </label>
            <textarea id="peristiwa"
                      name="kendala_peristiwa"
                      class="form-control"
                      rows="4"
                      placeholder="Jelaskan kondisi atau peristiwa yang diamati selama pembelajaran..."></textarea>
        </div>

        <div class="mb-3">
            <label for="tindakan" class="form-label fw-semibold">
                Tindakan / Penyesuaian Guru
            </label>
            <textarea id="tindakan"
                      name="tindakan"
                      class="form-control"
                      rows="4"
                      placeholder="Tuliskan tindakan atau penyesuaian pembelajaran yang dilakukan..."></textarea>
        </div>

        <div class="mb-3">
            <label for="hasil" class="form-label fw-semibold">
                Respons / Hasil Setelah Tindakan
            </label>
            <textarea id="hasil"
                      name="hasil_tindakan"
                      class="form-control"
                      rows="4"
                      placeholder="Jelaskan respons siswa setelah tindakan diberikan..."></textarea>
        </div>

        <div class="mb-4">
            <label for="tambahan" class="form-label fw-semibold">
                Catatan Tambahan
            </label>
            <textarea id="tambahan"
                      name="catatan_tambahan"
                      class="form-control"
                      rows="3"
                      placeholder="Tambahkan informasi lain jika diperlukan..."></textarea>
        </div>

        <hr>

        <div class="d-flex justify-content-end gap-2 flex-wrap">
            <button type="button"
                    class="btn btn-outline-primary"
                    disabled>
                Simpan Draft
            </button>

            <button type="button"
                    class="btn btn-primary"
                    disabled>
                Kirim ke GPK
            </button>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Mode preview: isian belum disimpan.
            Penyimpanan dan pengiriman ke GPK
            akan diaktifkan pada tahap integrasi database.
        </p>

    </section>
</x-layouts.preview>
