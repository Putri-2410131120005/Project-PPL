<x-layouts.preview :role="$role" :roles="$previewRoles" active="classes-mapel" :title="$title">
    <x-preview.topbar :title="$title" subtitle="Simpan nama mata pelajaran yang dipakai dalam proses pembelajaran." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.admin.subjects.index') }}">Mata Pelajaran</a> <span>/</span> {{ $title }}</div>

    <section class="abk-panel abk-user-form">
        <form method="POST" action="{{ $mataPelajaran->exists ? route('dev.admin.subjects.update', $mataPelajaran) : route('dev.admin.subjects.store') }}"
            data-confirm-form
            data-confirm-title="{{ $mataPelajaran->exists ? 'Simpan perubahan mata pelajaran?' : 'Tambah mata pelajaran?' }}"
            data-confirm-message="{{ $mataPelajaran->exists ? 'Perubahan mata pelajaran akan disimpan.' : 'Mata pelajaran baru akan tersedia untuk pemetaan guru dan catatan perkembangan.' }}"
            data-confirm-subject="{{ $mataPelajaran->exists ? $mataPelajaran->nama_mapel : 'Data mata pelajaran baru' }}"
            data-confirm-button="{{ $mataPelajaran->exists ? 'Simpan Perubahan' : 'Tambah Mata Pelajaran' }}">
            @csrf
            @if ($mataPelajaran->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="nama_mapel">Nama mata pelajaran</label>
                    <input class="form-control @error('nama_mapel') is-invalid @enderror" id="nama_mapel" name="nama_mapel" value="{{ old('nama_mapel', $mataPelajaran->nama_mapel) }}" required maxlength="100">
                    @error('nama_mapel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status_mapel">Status mata pelajaran</label>
                    <select class="form-select @error('status_mapel') is-invalid @enderror" id="status_mapel" name="status_mapel" required>
                        @foreach ($subjectStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status_mapel', $mataPelajaran->status_mapel ?: 'Aktif') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Mata pelajaran yang diarsipkan tetap menyimpan catatan dan pemetaan lama.</div>
                    @error('status_mapel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <fieldset>
                        <legend class="form-label mb-2">Kelas terkait</legend>
                        <p class="small text-muted">Pilih semua kelas yang memiliki mata pelajaran ini.</p>
                        <div class="row g-2">
                            @forelse ($classes as $class)
                                <div class="col-12 col-md-6 col-xl-4">
                                    <label class="abk-check-option">
                                        <input type="checkbox" name="id_kelas[]" value="{{ $class->id_kelas }}" @checked(in_array($class->id_kelas, old('id_kelas', $linkedClassIds->all())))>
                                        <span><strong>{{ $class->tingkat }} — {{ $class->nama_kelas }}</strong><small>{{ $class->tahun_ajaran }}{{ $class->status_kelas === 'Diarsipkan' ? ' · Diarsipkan' : '' }}</small></span>
                                    </label>
                                </div>
                            @empty
                                <div class="col-12"><div class="alert alert-warning mb-0">Belum ada kelas aktif. Tambahkan kelas sebelum membuat mata pelajaran.</div></div>
                            @endforelse
                        </div>
                        @error('id_kelas')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('id_kelas.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </fieldset>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="abk-primary-button" type="submit" @disabled($classes->isEmpty())>{{ $mataPelajaran->exists ? 'Simpan Perubahan' : 'Tambah Mata Pelajaran' }}</button>
                <a class="abk-button text-decoration-none" href="{{ route('dev.admin.subjects.index') }}">Batal</a>
            </div>
        </form>
    </section>
</x-layouts.preview>
