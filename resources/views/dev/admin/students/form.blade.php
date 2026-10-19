<x-layouts.preview :role="$role" :roles="$previewRoles" active="students" :title="$title">
    <x-preview.topbar :title="$title" subtitle="Lengkapi data induk siswa sesuai dokumen sekolah." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.admin.students.index') }}">Siswa ABK</a> <span>/</span> {{ $title }}</div>

    <section class="abk-panel abk-user-form">
        <form method="POST" enctype="multipart/form-data" action="{{ $student->exists ? route('dev.admin.students.update', $student) : route('dev.admin.students.store') }}"
            data-confirm-form
            data-confirm-title="{{ $student->exists ? 'Simpan perubahan data siswa?' : 'Tambah data siswa?' }}"
            data-confirm-message="{{ $student->exists ? 'Perubahan data siswa akan disimpan.' : 'Data siswa ABK baru akan ditambahkan ke sistem.' }}"
            data-confirm-subject="{{ $student->exists ? $student->nama_siswa.' / NIS '.$student->nis : 'Data siswa ABK baru' }}"
            data-confirm-button="{{ $student->exists ? 'Simpan Perubahan' : 'Tambah Siswa' }}">
            @csrf
            @if ($student->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="nis">NIS</label>
                    <input class="form-control @error('nis') is-invalid @enderror" id="nis" name="nis" value="{{ old('nis', $student->nis) }}" required maxlength="30">
                    @error('nis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="nama_siswa">Nama siswa</label>
                    <input class="form-control @error('nama_siswa') is-invalid @enderror" id="nama_siswa" name="nama_siswa" value="{{ old('nama_siswa', $student->nama_siswa) }}" required maxlength="100">
                    @error('nama_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="id_kelas">Kelas</label>
                    <select class="form-select @error('id_kelas') is-invalid @enderror" id="id_kelas" name="id_kelas" required>
                        <option value="">Pilih kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id_kelas }}" @selected((string) old('id_kelas', $student->id_kelas) === (string) $class->id_kelas)>{{ $class->tingkat }} — {{ $class->nama_kelas }} ({{ $class->tahun_ajaran }})</option>
                        @endforeach
                    </select>
                    @error('id_kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status_siswa">Status siswa</label>
                    <select class="form-select @error('status_siswa') is-invalid @enderror" id="status_siswa" name="status_siswa" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status_siswa', $student->status_siswa ?: 'Aktif') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('status_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="foto">Foto anak <span class="text-muted">(opsional)</span></label>
                    <div class="abk-photo-field">
                        @if ($student->foto_url)
                            <img class="abk-photo-preview" src="{{ $student->foto_url }}" alt="Foto {{ $student->nama_siswa }}">
                        @else
                            <div class="abk-photo-placeholder" aria-hidden="true">{{ strtoupper(substr($student->nama_siswa ?: 'S', 0, 1)) }}</div>
                        @endif
                        <div class="flex-grow-1">
                            <input type="file" class="form-control @error('foto') is-invalid @enderror" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Format JPG, PNG, atau WebP. Ukuran maksimal 2 MB.</div>
                            @if ($student->exists && $student->foto)
                                <div class="form-text">Biarkan kosong jika tidak ingin mengganti foto saat ini.</div>
                            @endif
                            @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="kebutuhan_khusus">Kebutuhan khusus</label>
                    <input class="form-control @error('kebutuhan_khusus') is-invalid @enderror" id="kebutuhan_khusus" name="kebutuhan_khusus" value="{{ old('kebutuhan_khusus', $student->kebutuhan_khusus) }}" required maxlength="150" placeholder="Contoh: hambatan penglihatan, kesulitan belajar, atau kebutuhan pendampingan tertentu">
                    @error('kebutuhan_khusus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-4"><button class="abk-primary-button" type="submit">{{ $student->exists ? 'Simpan Perubahan' : 'Tambah Siswa' }}</button><a class="abk-button text-decoration-none" href="{{ route('dev.admin.students.index') }}">Batal</a></div>
        </form>
    </section>
</x-layouts.preview>
