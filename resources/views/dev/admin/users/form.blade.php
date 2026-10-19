<x-layouts.preview :role="$role" :roles="$previewRoles" active="users" :title="$title">
    <x-preview.topbar :title="$title" subtitle="Lengkapi data akun pengguna sesuai kebutuhan sekolah." :profile="$profile" />
    <div class="abk-breadcrumb"><a href="{{ route('dev.preview', 'admin') }}">Dashboard</a> <span>/</span> <a href="{{ route('dev.admin.users.index') }}">Pengguna</a> <span>/</span> {{ $title }}</div>
    <section class="abk-panel abk-user-form">
        <form method="POST" action="{{ $formAction }}"
            data-confirm-form
            data-confirm-title="{{ $user->exists ? 'Simpan perubahan pengguna?' : ($title === 'Tambah Guru' ? 'Tambah data guru?' : 'Tambah pengguna?') }}"
            data-confirm-message="{{ $user->exists ? 'Perubahan data pengguna akan disimpan.' : ($title === 'Tambah Guru' ? 'Data guru baru akan ditambahkan sesuai peran yang dipilih.' : 'Akun pengguna baru akan ditambahkan sesuai role yang dipilih.') }}"
            data-confirm-subject="{{ $user->exists ? $user->nama : 'Data pengguna baru' }}"
            data-confirm-button="{{ $user->exists ? 'Simpan Perubahan' : ($title === 'Tambah Guru' ? 'Tambah Guru' : 'Tambah Pengguna') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-12 col-md-6"><label class="form-label" for="nama">Nama pengguna</label><input class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $user->nama) }}" required maxlength="100">@error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-6"><label class="form-label" for="email">Email <span class="text-muted d-none" id="email-optional">(opsional)</span></label><input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="100">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-6"><label class="form-label" for="id_role">Role</label><select class="form-select @error('id_role') is-invalid @enderror" id="id_role" name="id_role" required><option value="">Pilih role</option>@foreach ($roles as $availableRole)<option value="{{ $availableRole->id_role }}" data-requires-nip="{{ in_array($availableRole->nama_role, ['Guru Mapel', 'Tim Inklusi', 'GPK', 'Kepala Sekolah'], true) ? 'true' : 'false' }}" data-is-subject-teacher="{{ $availableRole->nama_role === 'Guru Mapel' ? 'true' : 'false' }}" data-is-companion="{{ in_array($availableRole->nama_role, ['GPK', 'Tim Inklusi'], true) ? 'true' : 'false' }}" data-is-parent="{{ $availableRole->nama_role === 'Orang Tua' ? 'true' : 'false' }}" @selected((string) old('id_role', $user->id_role) === (string) $availableRole->id_role)>{{ $availableRole->nama_role }}</option>@endforeach</select>@error('id_role')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-6"><label class="form-label" for="status_akun">Status akun</label><select class="form-select @error('status_akun') is-invalid @enderror" id="status_akun" name="status_akun" required><option value="Aktif" @selected(old('status_akun', $user->status_akun ?: 'Aktif') === 'Aktif')>Aktif</option><option value="Nonaktif" @selected(old('status_akun', $user->status_akun) === 'Nonaktif')>Nonaktif</option></select>@error('status_akun')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-6" id="username-field"><label class="form-label" for="username">Username <span class="text-muted">(opsional)</span></label><input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" maxlength="50">@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12 col-md-6" id="nip-field"><label class="form-label" for="nip">NIP</label><input class="form-control @error('nip') is-invalid @enderror" id="nip" name="nip" value="{{ old('nip', $user->nip) }}" maxlength="30">@error('nip')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>

                <div class="col-12 d-none" id="teaching-assignment-fields">
                    <div class="abk-role-fields">
                        <div class="abk-role-fields-title">Pilih Mata Pelajaran dan Kelas yang Diajar</div>
                        @php($selectedTeachingClassIds = collect(old('id_kelas', $initialTeachingClassIds))->map(fn ($id) => (string) $id))
                        <div class="row g-3">
                            <div class="col-12 col-md-6"><label class="form-label" for="id_mapel">Mata pelajaran</label><select class="form-select @error('id_mapel') is-invalid @enderror" id="id_mapel" name="id_mapel"><option value="">Pilih mata pelajaran</option>@foreach ($subjects as $subject)<option value="{{ $subject->id_mapel }}" data-class-ids="{{ $subject->kelas->pluck('id_kelas')->join(',') }}" @selected((string) old('id_mapel', $initialAssignment?->id_mapel) === (string) $subject->id_mapel)>{{ $subject->nama_mapel }}</option>@endforeach</select>@error('id_mapel')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12">
                                <div class="form-label">Kelas yang diajar</div>
                                <div class="row g-2" id="class-checklist">
                                    @foreach ($classes as $class)
                                        <div class="col-12 col-md-6" data-class-option data-class-id="{{ $class->id_kelas }}">
                                            <label class="abk-check-option">
                                                <input class="form-check-input" type="checkbox" name="id_kelas[]" value="{{ $class->id_kelas }}" @checked($selectedTeachingClassIds->contains((string) $class->id_kelas))>
                                                <span><strong>{{ $class->tingkat }} — {{ $class->nama_kelas }}</strong><small>{{ $class->tahun_ajaran }}</small></span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="small text-muted mt-2" id="class-checklist-hint">Pilih mata pelajaran untuk menampilkan kelas yang tersedia.</div>
                                @error('id_kelas')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                                @error('id_kelas.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 d-none" id="companion-assignment-fields">
                    <div class="abk-role-fields">
                        <div class="abk-role-fields-title">Siswa Dampingan</div>
                        @php($selectedCompanionStudentIds = collect(old('id_siswa_pendamping', $initialCompanionStudentIds))->map(fn ($id) => (string) $id))
                        <div class="row g-2" id="companion-student-checklist">
                            @foreach ($students as $student)
                                <div class="col-12 col-md-6">
                                    <label class="abk-check-option">
                                        <input class="form-check-input" type="checkbox" name="id_siswa_pendamping[]" value="{{ $student->id_siswa }}" @checked($selectedCompanionStudentIds->contains((string) $student->id_siswa))>
                                        <span><strong>{{ $student->nama_siswa }}</strong><small>NIS {{ $student->nis }}</small></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="small text-muted mt-2"><span id="companion-selection-count">{{ $selectedCompanionStudentIds->count() }}</span>/2 siswa dipilih</div>
                        @error('id_siswa_pendamping')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('id_siswa_pendamping.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-12 d-none" id="parent-fields">
                    <div class="abk-role-fields">
                        <div class="abk-role-fields-title">Data Orang Tua/Wali</div>
                        <p class="abk-role-fields-description">Pilih siswa utama. Username memakai format ortu_NIS dan password sementara memakai nama depan serta NIS siswa.</p>
                        @if ($students->isEmpty())
                            <div class="alert alert-warning">Belum ada siswa aktif. Tambahkan atau aktifkan data siswa terlebih dahulu.</div>
                        @endif
                        <div class="row g-3">
                            <div class="col-12 col-md-6"><label class="form-label" for="id_siswa">Siswa utama</label><select class="form-select @error('id_siswa') is-invalid @enderror" id="id_siswa" name="id_siswa"><option value="">Pilih siswa ABK</option>@foreach ($students as $student)<option value="{{ $student->id_siswa }}" @selected((string) old('id_siswa') === (string) $student->id_siswa)>{{ $student->nis }} - {{ $student->nama_siswa }}</option>@endforeach</select>@error('id_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12 col-md-6"><label class="form-label" for="hubungan">Hubungan</label><select class="form-select @error('hubungan') is-invalid @enderror" id="hubungan" name="hubungan"><option value="">Pilih hubungan</option>@foreach (['Ibu', 'Ayah', 'Wali'] as $relationship)<option value="{{ $relationship }}" @selected(old('hubungan') === $relationship)>{{ $relationship }}</option>@endforeach</select>@error('hubungan')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12 col-md-6"><label class="form-label" for="no_telepon">Nomor telepon <span class="text-muted">(opsional)</span></label><input class="form-control @error('no_telepon') is-invalid @enderror" id="no_telepon" name="no_telepon" value="{{ old('no_telepon') }}" maxlength="20">@error('no_telepon')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12 col-md-6"><label class="form-label" for="alamat">Alamat <span class="text-muted">(opsional)</span></label><input class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat" value="{{ old('alamat') }}" maxlength="255">@error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-4"><button class="abk-primary-button" type="submit">{{ $user->exists ? 'Simpan Perubahan' : ($title === 'Tambah Guru' ? 'Tambah Guru' : 'Tambah Pengguna') }}</button><a class="abk-button text-decoration-none" href="{{ $cancelUrl }}">Batal</a></div>
        </form>
    </section>
    <script>
        const roleSelect = document.getElementById('id_role');
        const emailInput = document.getElementById('email');
        const emailOptional = document.getElementById('email-optional');
        const nipField = document.getElementById('nip-field');
        const nipInput = document.getElementById('nip');
        const usernameField = document.getElementById('username-field');
        const usernameInput = document.getElementById('username');
        const assignmentFields = document.getElementById('teaching-assignment-fields');
        const subjectSelect = document.getElementById('id_mapel');
        const classOptions = document.querySelectorAll('[data-class-option]');
        const classChecklistHint = document.getElementById('class-checklist-hint');
        const companionFields = document.getElementById('companion-assignment-fields');
        const companionInputs = document.querySelectorAll('input[name="id_siswa_pendamping[]"]');
        const companionSelectionCount = document.getElementById('companion-selection-count');
        const parentFields = document.getElementById('parent-fields');
        const parentInputs = parentFields.querySelectorAll('input, select');

        const syncRelatedClasses = () => {
            const selectedSubject = subjectSelect.options[subjectSelect.selectedIndex];
            const relatedClassIds = (selectedSubject?.dataset.classIds ?? '').split(',').filter(Boolean);

            classOptions.forEach((option) => {
                const checkbox = option.querySelector('input');
                const isAvailable = Boolean(selectedSubject?.value) && relatedClassIds.includes(option.dataset.classId);
                option.classList.toggle('d-none', !isAvailable);
                checkbox.disabled = !isAvailable;
                if (!isAvailable) checkbox.checked = false;
            });
            classChecklistHint.classList.toggle('d-none', Boolean(selectedSubject?.value));
        };

        const syncCompanionLimit = () => {
            const selectedRole = roleSelect.options[roleSelect.selectedIndex];
            const isCompanion = selectedRole?.dataset.isCompanion === 'true';
            emailInput.required = !isParent;
            emailOptional.classList.toggle('d-none', !isParent);
            const selectedCount = Array.from(companionInputs).filter((input) => input.checked).length;
            companionSelectionCount.textContent = selectedCount;
            companionInputs.forEach((input) => {
                input.disabled = !isCompanion || (!input.checked && selectedCount >= 2);
            });
        };

        const syncRoleFields = () => {
            const selectedRole = roleSelect.options[roleSelect.selectedIndex];
            const required = selectedRole?.dataset.requiresNip === 'true';
            const isParent = selectedRole?.dataset.isParent === 'true';
            const isCompanion = selectedRole?.dataset.isCompanion === 'true';
            nipField.classList.toggle('d-none', !required);
            nipInput.required = required;
            if (!required) nipInput.value = '';
            assignmentFields.classList.toggle('d-none', selectedRole?.dataset.isSubjectTeacher !== 'true');
            companionFields.classList.toggle('d-none', !isCompanion);
            parentFields.classList.toggle('d-none', !isParent);
            usernameField.classList.toggle('d-none', isParent);
            usernameInput.disabled = isParent;
            parentInputs.forEach((input) => {
                input.disabled = !isParent;
                input.required = isParent && ['id_siswa', 'hubungan'].includes(input.id);
            });
            syncCompanionLimit();
        };

        roleSelect.addEventListener('change', syncRoleFields);
        subjectSelect.addEventListener('change', syncRelatedClasses);
        companionInputs.forEach((input) => input.addEventListener('change', syncCompanionLimit));
        syncRoleFields();
        syncRelatedClasses();
    </script>
</x-layouts.preview>
