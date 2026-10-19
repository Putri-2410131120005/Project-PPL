<x-layouts.preview :role="$role" :roles="$previewRoles" active="classes-mapel" :title="$title">
    <x-preview.topbar :title="$title" subtitle="Simpan data kelas sesuai struktur akademik sekolah." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.admin.classes.index') }}">Kelas</a> <span>/</span> {{ $title }}</div>

    <section class="abk-panel abk-user-form">
        <form method="POST" action="{{ $kelas->exists ? route('dev.admin.classes.update', $kelas) : route('dev.admin.classes.store') }}"
            data-confirm-form
            data-confirm-title="{{ $kelas->exists ? 'Simpan perubahan kelas?' : 'Tambah kelas?' }}"
            data-confirm-message="{{ $kelas->exists ? 'Perubahan data kelas akan disimpan.' : 'Kelas baru akan tersedia untuk pengelolaan siswa dan pemetaan pembelajaran.' }}"
            data-confirm-subject="{{ $kelas->exists ? $kelas->nama_kelas : 'Data kelas baru' }}"
            data-confirm-button="{{ $kelas->exists ? 'Simpan Perubahan' : 'Tambah Kelas' }}">
            @csrf
            @if ($kelas->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="nama_kelas">Nama kelas</label>
                    <input class="form-control @error('nama_kelas') is-invalid @enderror" id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas', $kelas->nama_kelas) }}" required maxlength="50">
                    @error('nama_kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="tingkat">Tingkat</label>
                    <input class="form-control @error('tingkat') is-invalid @enderror" id="tingkat" name="tingkat" value="{{ old('tingkat', $kelas->tingkat) }}" required maxlength="10" placeholder="Contoh: X atau 1">
                    @error('tingkat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="tahun_ajaran">Tahun ajaran</label>
                    <input class="form-control @error('tahun_ajaran') is-invalid @enderror" id="tahun_ajaran" name="tahun_ajaran" value="{{ old('tahun_ajaran', $kelas->tahun_ajaran) }}" required maxlength="20" placeholder="Contoh: 2026/2027">
                    @error('tahun_ajaran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status_kelas">Status kelas</label>
                    <select class="form-select @error('status_kelas') is-invalid @enderror" id="status_kelas" name="status_kelas" required>
                        @foreach ($classStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status_kelas', $kelas->status_kelas ?: 'Aktif') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Kelas yang diarsipkan tetap menyimpan siswa dan riwayat pemetaan.</div>
                    @error('status_kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="abk-primary-button" type="submit">{{ $kelas->exists ? 'Simpan Perubahan' : 'Tambah Kelas' }}</button>
                <a class="abk-button text-decoration-none" href="{{ route('dev.admin.classes.index') }}">Batal</a>
            </div>
        </form>
    </section>
</x-layouts.preview>
