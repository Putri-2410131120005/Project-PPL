<x-layouts.preview role="admin" :roles="$previewRoles" active="users" :title="$parent->exists ? 'Ubah Akun Orang Tua/Wali' : 'Buat Akun Orang Tua/Wali'">
    <x-preview.topbar :title="$parent->exists ? 'Ubah Akun Orang Tua/Wali' : 'Buat Akun Orang Tua/Wali'" subtitle="Akun Orang Tua/Wali terhubung langsung ke data siswa." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.admin.users.index', array_filter(['role' => $parent->id_role])) }}">Pengguna</a> <span>/</span> {{ $parent->exists ? 'Ubah Akun Orang Tua/Wali' : 'Buat Akun Orang Tua/Wali' }}</div>

    <section class="abk-panel abk-user-form">
        <div class="abk-login-note mt-0">
            @if ($parent->exists)
                Username akun menggunakan format ortu_NIS siswa utama. Bila siswa utama diperbaiki, username akan diperbarui otomatis tanpa mengubah password yang sedang digunakan.
            @else
                Pilih siswa untuk membuat akun Orang Tua/Wali. Username menggunakan format ortu_NIS dan password sementara dibentuk dari nama depan serta NIS siswa.
            @endif
        </div>

        @if (! $parent->exists && $students->isEmpty())
            <div class="alert alert-warning mb-0">Belum ada data siswa. Tambahkan Siswa ABK terlebih dahulu sebelum membuat akun Orang Tua/Wali.</div>
        @endif

        <form method="POST" action="{{ $parent->exists ? route('dev.admin.users.update', $parent) : route('dev.admin.users.parents.store') }}"
            data-confirm-form
            data-confirm-title="{{ $parent->exists ? 'Simpan perubahan akun Orang Tua/Wali?' : 'Buat akun Orang Tua/Wali?' }}"
            data-confirm-message="{{ $parent->exists ? 'Perubahan data akun akan disimpan. Jika siswa utama diganti, username login akan ikut diperbarui.' : 'Username dan password sementara akan dibentuk dari data siswa yang dipilih.' }}"
            data-confirm-subject="{{ $parent->exists ? $parent->nama : 'Akun Orang Tua/Wali baru' }}"
            data-confirm-button="{{ $parent->exists ? 'Simpan Perubahan' : 'Buat Akun' }}">
            @csrf
            @if ($parent->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="nama">Nama Orang Tua/Wali</label>
                    <input class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $parent->nama) }}" required maxlength="100">
                    @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="email">Email <span class="text-muted">(opsional)</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $parent->email) }}" maxlength="100">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if ($parent->exists)
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="username">Username login</label>
                        <input class="form-control" id="username" value="{{ $parent->username ?: 'Belum terhubung ke NIS siswa' }}" readonly>
                        <div class="form-text">Berubah otomatis saat siswa utama diganti.</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="id_siswa">Siswa utama</label>
                        <select class="form-select @error('id_siswa') is-invalid @enderror" id="id_siswa" name="id_siswa" required>
                            <option value="">Pilih siswa ABK</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id_siswa }}" @selected((string) old('id_siswa', $linkedStudent?->id_siswa) === (string) $student->id_siswa)>{{ $student->nis }} - {{ $student->nama_siswa }}</option>
                            @endforeach
                        </select>
                        @error('id_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="hubungan">Hubungan dengan siswa utama</label>
                        <select class="form-select @error('hubungan') is-invalid @enderror" id="hubungan" name="hubungan" required>
                            <option value="">Pilih hubungan</option>
                            @foreach (['Ibu', 'Ayah', 'Wali'] as $relationship)
                                <option value="{{ $relationship }}" @selected(old('hubungan', $primaryRelation?->hubungan) === $relationship)>{{ $relationship }}</option>
                            @endforeach
                        </select>
                        @error('hubungan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @else
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="id_siswa">Siswa terhubung</label>
                        <select class="form-select @error('id_siswa') is-invalid @enderror" id="id_siswa" name="id_siswa" required @disabled($students->isEmpty())>
                            <option value="">Pilih siswa ABK</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id_siswa }}" data-username="ortu_{{ $student->nis }}" @selected((string) old('id_siswa') === (string) $student->id_siswa)>{{ $student->nis }} - {{ $student->nama_siswa }}</option>
                            @endforeach
                        </select>
                        @error('id_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="hubungan">Hubungan</label>
                        <select class="form-select @error('hubungan') is-invalid @enderror" id="hubungan" name="hubungan" required @disabled($students->isEmpty())>
                            <option value="">Pilih hubungan</option>
                            @foreach (['Ibu', 'Ayah', 'Wali'] as $relationship)
                                <option value="{{ $relationship }}" @selected(old('hubungan') === $relationship)>{{ $relationship }}</option>
                            @endforeach
                        </select>
                        @error('hubungan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="username-preview">Username login</label>
                        <input class="form-control" id="username-preview" value="" placeholder="Terisi setelah siswa dipilih" readonly>
                    </div>
                @endif
                <div class="col-12 col-md-6">
                    <label class="form-label" for="no_telepon">Nomor telepon <span class="text-muted">(opsional)</span></label>
                    <input class="form-control @error('no_telepon') is-invalid @enderror" id="no_telepon" name="no_telepon" value="{{ old('no_telepon', $parentProfile?->no_telepon) }}" maxlength="20">
                    @error('no_telepon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="alamat">Alamat <span class="text-muted">(opsional)</span></label>
                    <input class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" value="{{ old('alamat', $parentProfile?->alamat) }}" maxlength="255">
                    @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status_akun">Status akun</label>
                    <select class="form-select @error('status_akun') is-invalid @enderror" id="status_akun" name="status_akun" required>
                        <option value="Aktif" @selected(old('status_akun', $parent->status_akun ?: 'Aktif') === 'Aktif')>Aktif</option>
                        <option value="Nonaktif" @selected(old('status_akun', $parent->status_akun) === 'Nonaktif')>Nonaktif</option>
                    </select>
                    @error('status_akun')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="abk-primary-button" type="submit" @disabled(! $parent->exists && $students->isEmpty())>{{ $parent->exists ? 'Simpan Perubahan' : 'Buat Akun dan Password Sementara' }}</button>
                <a class="abk-button text-decoration-none" href="{{ route('dev.admin.users.index', array_filter(['role' => $parent->id_role])) }}">Batal</a>
            </div>
        </form>
    </section>

    @if (! $parent->exists)
        <script>
            const studentSelect = document.getElementById('id_siswa');
            const usernamePreview = document.getElementById('username-preview');

            if (studentSelect && usernamePreview) {
                const syncUsername = () => {
                    usernamePreview.value = studentSelect.options[studentSelect.selectedIndex]?.dataset.username ?? '';
                };

                studentSelect.addEventListener('change', syncUsername);
                syncUsername();
            }
        </script>
    @endif
</x-layouts.preview>
