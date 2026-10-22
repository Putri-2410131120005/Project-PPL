<x-layouts.preview :role="$role" :roles="$roles" title="Detail Catatan Perkembangan" active="verification">
    <x-preview.topbar title="Detail Catatan Perkembangan" subtitle="Lihat peristiwa, tindakan guru, dan respons siswa."
        :profile="$profile" />

    <div class="abk-breadcrumb mb-3">
        <a href="{{ route('dev.guru-mapel.notes.status') }}">
            Status Verifikasi
        </a>
        / Detail Catatan
    </div>

    <section class="abk-panel p-4">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h2 class="h5 fw-bold">
                    {{ $catatan['nama'] }}
                </h2>

                <p class="text-muted mb-2">
                    {{ $catatan['kelas'] }}
                </p>

                <span class="badge text-bg-secondary">
                    DATA DEMO
                </span>

                <span class="badge text-bg-info">
                    {{ $catatan['status'] }}
                </span>
            </div>

            <a href="{{ route('dev.guru-mapel.notes.status') }}" class="btn btn-outline-secondary">
                Kembali
            </a>
        </div>

        <div class="row g-3 mb-4">

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Sumber Catatan
                </label>
                <input class="form-control" value="Guru Mata Pelajaran" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Mata Pelajaran
                </label>
                <input class="form-control" value="{{ $catatan['mapel'] }}" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Nama Guru
                </label>
                <input class="form-control" value="{{ $catatan['guru'] }}" readonly>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Tanggal Catatan
                </label>
                <input class="form-control" value="{{ $catatan['tanggal'] }}" readonly>
            </div>
        </div>

        <hr>

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

                <textarea class="form-control" rows="3" readonly>{{ $catatan[$field] }}</textarea>
            </div>

        @endforeach

        <hr>

        <div class="alert alert-info mb-0">
            <strong>Status Verifikasi:</strong>
            {{ $catatan['status'] }}

            <p class="small mt-2 mb-0">
                Ini hanya preview status catatan.
                Proses verifikasi dan publikasi sebenarnya
                dilakukan oleh GPK.
            </p>
        </div>


        @if ($catatan['status'] === 'Dikembalikan')
            <div class="alert alert-warning mt-3">
                <strong>Catatan Perbaikan GPK</strong>
                <p class="mb-0 mt-2">
                    {{ $catatan['alasan_gpk'] ?? 'Catatan perlu diperbaiki.' }}
                </p>
            </div>

            <div class="mt-3">
                <a href="{{ route('dev.guru-mapel.notes.revise', ['id' => request()->route('id')]) }}"
                    class="btn btn-primary">
                    Perbaiki Catatan
                </a>
            </div>
        @endif
    </section>
</x-layouts.preview>