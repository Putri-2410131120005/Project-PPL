<?php

namespace App\Http\Controllers\Dev;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\ParentCredentials;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends AdminController
{
    /** @var list<string> */
    private const STAFF_ROLE_NAMES = ['Guru Mapel', 'Tim Inklusi', 'GPK', 'Kepala Sekolah'];

    /** @var list<string> */
    private const COMPANION_ROLE_NAMES = ['GPK', 'Tim Inklusi'];

    /** @var list<string> */
    private const MAPPING_EDUCATOR_ROLE_NAMES = ['Guru Mapel', 'Tim Inklusi', 'GPK'];

    /** @var list<string> */
    private const ADMIN_ROLE_NAMES = ['Admin', 'Admin / Pengelola'];

    public function index(Request $request): View
    {
        $query = Pengguna::query()
            ->with(['role', 'orangTua.relasiSiswa.siswa'])
            ->orderBy('nama');
        $roles = Role::query()->orderBy('nama_role')->get();
        $parentRoleId = $roles->firstWhere('nama_role', 'Orang Tua')?->id_role;

        if ($search = trim((string) $request->string('cari'))) {
            $query->where(fn ($users) => $users
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%"));
        }

        if ($roleId = $request->integer('role')) {
            $query->where('id_role', $roleId);
        }

        $users = $query->paginate($this->perPage($request))->withQueryString();

        if ($parentRoleId !== null && $request->integer('role') === $parentRoleId) {
            $users->getCollection()->each(function (Pengguna $user): void {
                $user->setAttribute('visible_temporary_password', ParentCredentials::visibleTemporaryPassword($user));
            });
        }

        return view('dev.admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'isParentFilter' => $parentRoleId !== null && $request->integer('role') === $parentRoleId,
            'filters' => $request->only(['cari', 'role']),
            ...$this->viewContext(),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new Pengguna, 'Tambah Pengguna');
    }

    public function staffIndex(Request $request): View
    {
        $query = Pengguna::query()
            ->with('role')
            ->whereHas('role', fn ($roles) => $roles->whereIn('nama_role', self::STAFF_ROLE_NAMES))
            ->orderBy('nama');

        if ($search = trim((string) $request->string('cari'))) {
            $query->where(fn ($users) => $users
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%"));
        }

        if ($roleId = $request->integer('role')) {
            $query->where('id_role', $roleId);
        }

        return view('dev.admin.users.staff-index', [
            'users' => $query->paginate($this->perPage($request))->withQueryString(),
            'roles' => Role::query()->whereIn('nama_role', self::STAFF_ROLE_NAMES)->orderBy('nama_role')->get(),
            'filters' => $request->only(['cari', 'role']),
            ...$this->viewContext(),
        ]);
    }

    public function createStaff(): View
    {
        return $this->formView(
            new Pengguna,
            'Tambah Guru',
            true,
            route('dev.admin.staff.store'),
            route('dev.admin.staff.index')
        );
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $data = $this->validatedStaffUser($request, staffOnly: true);
        $this->createNonParentUser($data, $request);

        return redirect()->route('dev.admin.staff.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function editStaff(Pengguna $pengguna): View
    {
        abort_unless($this->isStaffRole($pengguna->id_role), 404);

        return $this->formView(
            $pengguna,
            'Ubah Data Guru',
            true,
            route('dev.admin.staff.update', $pengguna),
            route('dev.admin.staff.index')
        );
    }

    public function updateStaff(Request $request, Pengguna $pengguna): RedirectResponse
    {
        abort_unless($this->isStaffRole($pengguna->id_role), 404);

        $this->updateNonParentUser($pengguna, $this->validatedStaffUser($request, $pengguna, true), $request);

        return redirect()->route('dev.admin.staff.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function store(Request $request): RedirectResponse
    {
        $baseData = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => [
                Rule::requiredIf(fn (): bool => ! $this->isParentRole($request->integer('id_role'))),
                'nullable',
                'email',
                'max:100',
                Rule::unique('pengguna', 'email'),
            ],
            'id_role' => ['required', 'integer', Rule::exists('role', 'id_role')],
            'status_akun' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);
        $roleId = (int) $baseData['id_role'];

        if ($this->isParentRole($roleId)) {
            return $this->storeParent($request);
        }

        $data = $this->validatedStaffUser($request);
        $this->createNonParentUser($data, $request);

        return redirect()->route('dev.admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(Pengguna $pengguna): View
    {
        if ($this->isParentRole($pengguna->id_role)) {
            return $this->parentForm($pengguna);
        }

        return $this->formView($pengguna, 'Ubah Pengguna');
    }

    public function update(Request $request, Pengguna $pengguna): RedirectResponse
    {
        if ($this->isParentRole($pengguna->id_role)) {
            return $this->updateParent($request, $pengguna);
        }

        $this->updateNonParentUser($pengguna, $this->validatedStaffUser($request, $pengguna), $request);

        return redirect()->route('dev.admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleStatus(Pengguna $pengguna): RedirectResponse
    {
        try {
            $updated = DB::transaction(function () use ($pengguna): ?array {
                $target = Pengguna::query()
                    ->with('role')
                    ->lockForUpdate()
                    ->findOrFail($pengguna->getKey());

                $status = $target->status_akun === 'Aktif' ? 'Nonaktif' : 'Aktif';

                if ($status === 'Nonaktif' && $this->isLastActiveAdmin($target)) {
                    return null;
                }

                $target->update(['status_akun' => $status]);

                return ['nama' => $target->nama, 'status' => $status];
            }, 3);
        } catch (QueryException) {
            return back()->with('error', 'Status akun tidak dapat diubah karena data baru saja berubah. Muat ulang halaman lalu coba kembali.');
        }

        if ($updated === null) {
            return back()->with('error', 'Akun admin aktif terakhir tidak dapat dinonaktifkan. Tambahkan atau aktifkan admin lain terlebih dahulu.');
        }

        return back()->with('success', "Status {$updated['nama']} diubah menjadi {$updated['status']}.");
    }

    public function resetParentPassword(Pengguna $pengguna): RedirectResponse
    {
        abort_unless($this->isParentRole($pengguna->id_role), 404);

        $parent = DB::transaction(function () use ($pengguna): Pengguna {
            $account = Pengguna::query()
                ->with('orangTua.relasiSiswa.siswa')
                ->lockForUpdate()
                ->findOrFail($pengguna->getKey());
            $student = ParentCredentials::linkedStudent($account);

            if (! $student) {
                throw ValidationException::withMessages([
                    'password' => 'Password tidak dapat direset karena akun belum terhubung ke siswa.',
                ]);
            }

            $temporaryPassword = ParentCredentials::temporaryPassword($student);
            $account->update([
                'username' => ParentCredentials::username($student),
                'password' => Hash::make($temporaryPassword),
                'wajib_ganti_password' => true,
                'password_diubah_pada' => null,
            ]);
            $account->setAttribute('generated_temporary_password', $temporaryPassword);

            return $account;
        }, 3);

        return redirect()->route('dev.admin.users.index', ['role' => $parent->id_role])
            ->with('success', "Password akun {$parent->nama} berhasil direset.")
            ->with('temporary_parent_email', $parent->email)
            ->with('temporary_parent_username', $parent->username)
            ->with('temporary_parent_password', $parent->generated_temporary_password);
    }

    public function createParent(): View
    {
        return $this->parentForm();
    }

    public function parentsIndex(Request $request): View
    {
        $query = OrangTua::query()
            ->with(['pengguna.role', 'relasiSiswa.siswa'])
            ->orderBy('id_orang_tua');

        if ($search = trim((string) $request->string('cari'))) {
            $query->whereHas('pengguna', fn ($users) => $users
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%"));
        }

        return view('dev.admin.users.parents-index', [
            'parents' => $query->paginate($this->perPage($request))->withQueryString(),
            'filters' => $request->only(['cari']),
            ...$this->viewContext(),
        ]);
    }

    public function storeParent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('pengguna', 'email')],
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
            'hubungan' => ['required', Rule::in(['Ibu', 'Ayah', 'Wali'])],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'status_akun' => ['nullable', Rule::in(['Aktif', 'Nonaktif'])],
        ]);
        $parentRole = Role::query()->where('nama_role', 'Orang Tua')->firstOrFail();

        try {
            $credentials = DB::transaction(function () use ($data, $parentRole): array {
                $student = Siswa::query()->lockForUpdate()->findOrFail($data['id_siswa']);

                if ($student->status_siswa !== 'Aktif') {
                    throw ValidationException::withMessages([
                        'id_siswa' => 'Akun Orang Tua/Wali hanya dapat dibuat untuk siswa dengan status Aktif.',
                    ]);
                }

                if (RelasiOrangTuaSiswa::query()->where('id_siswa', $student->getKey())->exists()) {
                    throw ValidationException::withMessages([
                        'id_siswa' => 'Siswa ini sudah memiliki akun Orang Tua/Wali.',
                    ]);
                }

                $username = ParentCredentials::username($student);
                $temporaryPassword = ParentCredentials::temporaryPassword($student);

                if (Pengguna::query()->where('username', $username)->exists()) {
                    throw ValidationException::withMessages([
                        'id_siswa' => 'Username Orang Tua/Wali untuk NIS siswa ini sudah digunakan.',
                    ]);
                }

                $parent = Pengguna::create([
                    'id_role' => $parentRole->id_role,
                    'nama' => $data['nama'],
                    'username' => $username,
                    'email' => $data['email'] ?? null,
                    'password' => Hash::make($temporaryPassword),
                    'password_diubah_pada' => null,
                    'wajib_ganti_password' => true,
                    'status_akun' => $data['status_akun'] ?? 'Aktif',
                ]);
                $parentProfile = OrangTua::create([
                    'id_pengguna' => $parent->id_pengguna,
                    'no_telepon' => $data['no_telepon'] ?? null,
                    'alamat' => $data['alamat'] ?? null,
                ]);
                RelasiOrangTuaSiswa::create([
                    'id_orang_tua' => $parentProfile->id_orang_tua,
                    'id_siswa' => $student->id_siswa,
                    'hubungan' => $data['hubungan'],
                ]);

                return ['username' => $username, 'password' => $temporaryPassword];
            }, 3);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'id_siswa' => 'NIS siswa baru saja digunakan atau hubungan siswa berubah. Muat ulang halaman lalu coba kembali.',
            ]);
        }

        return redirect()->route('dev.admin.users.index', ['role' => $parentRole->id_role])
            ->with('success', 'Akun Orang Tua/Wali berhasil dibuat.')
            ->with('temporary_parent_email', $data['email'] ?? null)
            ->with('temporary_parent_username', $credentials['username'])
            ->with('temporary_parent_password', $credentials['password']);
    }

    private function formView(
        Pengguna $pengguna,
        string $title,
        bool $staffOnly = false,
        ?string $formAction = null,
        ?string $cancelUrl = null,
    ): View {
        $initialAssignments = $pengguna->exists
            ? $pengguna->relasiGuruMapelKelas()->orderBy('id_relasi_guru_mapel')->get()
            : collect();
        $initialAssignment = $initialAssignments->first();

        return view('dev.admin.users.form', [
            'user' => $pengguna,
            'roles' => Role::query()
                ->when($staffOnly, fn ($roles) => $roles->whereIn('nama_role', self::STAFF_ROLE_NAMES))
                ->orderBy('nama_role')
                ->get(),
            'subjects' => MataPelajaran::query()
                ->with('kelas:id_kelas')
                ->where('status_mapel', 'Aktif')
                ->orderBy('nama_mapel')
                ->get(),
            'classes' => Kelas::query()->where('status_kelas', 'Aktif')
                ->when(TahunAjaran::aktif(), fn ($classes, $year) => $classes->where('id_tahun_ajaran', $year->getKey()))
                ->orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'students' => Siswa::query()->where('status_siswa', 'Aktif')->orderBy('nama_siswa')->get(),
            'initialAssignment' => $initialAssignment,
            'initialTeachingClassIds' => $initialAssignment
                ? $initialAssignments->where('id_mapel', $initialAssignment->id_mapel)->pluck('id_kelas')->all()
                : [],
            'initialCompanionStudentIds' => $pengguna->exists
                ? $pengguna->relasiPendampingSiswa()->orderBy('id_relasi_pendamping')->pluck('id_siswa')->all()
                : [],
            'title' => $title,
            'formAction' => $formAction ?? ($pengguna->exists ? route('dev.admin.users.update', $pengguna) : route('dev.admin.users.store')),
            'cancelUrl' => $cancelUrl ?? route('dev.admin.users.index'),
            ...$this->viewContext(),
        ]);
    }

    private function parentForm(?Pengguna $pengguna = null): View
    {
        $pengguna ??= new Pengguna;
        $parentProfile = $pengguna->exists ? $pengguna->orangTua : null;
        $relations = $parentProfile?->relasiSiswa()
            ->with('siswa')
            ->orderBy('id_relasi_ortu')
            ->get() ?? collect();
        $primaryRelation = $relations->first(
            fn (RelasiOrangTuaSiswa $relation) => in_array($pengguna->username, [
                $relation->siswa?->nis,
                $relation->siswa ? ParentCredentials::username($relation->siswa) : null,
            ], true)
        ) ?? $relations->first();
        $linkedStudent = $primaryRelation?->siswa;

        return view('dev.admin.users.parent-form', [
            'parent' => $pengguna,
            'parentProfile' => $parentProfile,
            'primaryRelation' => $primaryRelation,
            'linkedStudent' => $linkedStudent,
            'students' => Siswa::query()
                ->where(function ($students) use ($linkedStudent): void {
                    $students->where('status_siswa', 'Aktif');

                    if ($linkedStudent) {
                        $students->orWhere('id_siswa', $linkedStudent->getKey());
                    }
                })
                ->orderBy('nama_siswa')
                ->get(),
            ...$this->viewContext(),
        ]);
    }

    private function updateParent(Request $request, Pengguna $pengguna): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('pengguna', 'email')->ignore($pengguna)],
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
            'hubungan' => ['required', Rule::in(['Ibu', 'Ayah', 'Wali'])],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'status_akun' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);

        try {
            DB::transaction(function () use ($data, $pengguna): void {
                $account = Pengguna::query()
                    ->lockForUpdate()
                    ->findOrFail($pengguna->getKey());
                $parentProfile = OrangTua::query()
                    ->where('id_pengguna', $account->getKey())
                    ->lockForUpdate()
                    ->first() ?? OrangTua::create(['id_pengguna' => $account->getKey()]);
                $relations = RelasiOrangTuaSiswa::query()
                    ->where('id_orang_tua', $parentProfile->getKey())
                    ->with('siswa')
                    ->lockForUpdate()
                    ->get();
                $primaryRelation = $relations->first(
                    fn (RelasiOrangTuaSiswa $relation) => in_array($account->username, [
                        $relation->siswa?->nis,
                        $relation->siswa ? ParentCredentials::username($relation->siswa) : null,
                    ], true)
                ) ?? $relations->first();
                $student = Siswa::query()->lockForUpdate()->findOrFail($data['id_siswa']);
                $isCurrentPrimary = $primaryRelation?->id_siswa === $student->getKey();

                if (! $isCurrentPrimary && $student->status_siswa !== 'Aktif') {
                    throw ValidationException::withMessages([
                        'id_siswa' => 'Siswa utama baru harus berstatus Aktif.',
                    ]);
                }

                if (! $isCurrentPrimary && RelasiOrangTuaSiswa::query()
                    ->where('id_siswa', $student->getKey())
                    ->where('id_orang_tua', '!=', $parentProfile->getKey())
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'id_siswa' => 'Siswa ini sudah memiliki akun Orang Tua/Wali.',
                    ]);
                }

                $username = ParentCredentials::username($student);

                if ($account->username !== $username && Pengguna::query()
                    ->where('username', $username)
                    ->where($account->getKeyName(), '!=', $account->getKey())
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'id_siswa' => 'NIS siswa ini sudah digunakan sebagai username akun lain.',
                    ]);
                }

                $selectedRelation = $relations->firstWhere('id_siswa', $student->getKey());

                if ($selectedRelation) {
                    $selectedRelation->update(['hubungan' => $data['hubungan']]);
                } else {
                    $selectedRelation = RelasiOrangTuaSiswa::create([
                        'id_orang_tua' => $parentProfile->getKey(),
                        'id_siswa' => $student->getKey(),
                        'hubungan' => $data['hubungan'],
                    ]);
                }

                if ($primaryRelation && ! $isCurrentPrimary) {
                    $primaryRelation->delete();
                }

                $account->update([
                    'nama' => $data['nama'],
                    'email' => $data['email'] ?? null,
                    'username' => $username,
                    'status_akun' => $data['status_akun'],
                ]);
                $parentProfile->update([
                    'no_telepon' => $data['no_telepon'] ?? null,
                    'alamat' => $data['alamat'] ?? null,
                ]);
            }, 3);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'id_siswa' => 'Keterhubungan siswa baru saja berubah. Muat ulang halaman lalu coba kembali.',
            ]);
        }

        return redirect()->route('dev.admin.users.index', ['role' => $pengguna->id_role])
            ->with('success', 'Data akun Orang Tua/Wali berhasil diperbarui.');
    }

    private function validatedStaffUser(Request $request, ?Pengguna $pengguna = null, bool $staffOnly = false): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('pengguna', 'username')->ignore($pengguna)],
            'email' => ['required', 'email', 'max:100', Rule::unique('pengguna', 'email')->ignore($pengguna)],
            'id_role' => ['required', 'integer', Rule::exists('role', 'id_role')],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('pengguna', 'nip')->ignore($pengguna)],
            'status_akun' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
        ]);

        if ($this->isParentRole((int) $data['id_role'])) {
            abort(422, 'Akun Orang Tua/Wali harus dibuat melalui alur Buat Akun Orang Tua.');
        }

        if ($staffOnly && ! $this->isStaffRole((int) $data['id_role'])) {
            throw ValidationException::withMessages([
                'id_role' => 'Halaman data guru hanya menerima role Guru Mapel, Tim Inklusi, GPK, atau Kepala Sekolah.',
            ]);
        }

        if ($this->isStaffRole((int) $data['id_role']) && blank($data['nip'] ?? null)) {
            throw ValidationException::withMessages([
                'nip' => 'NIP wajib diisi untuk Guru Mapel, Tim Inklusi, GPK, dan Kepala Sekolah.',
            ]);
        }

        if (! $this->isStaffRole((int) $data['id_role'])) {
            $data['nip'] = null;
        }

        return $data;
    }

    /**
     * Membuat akun dan pemetaan awal dalam satu transaksi.
     *
     * @param  array{id_role: int, nama: string, username: string|null, email: string, nip: string|null, status_akun: string}  $data
     */
    private function createNonParentUser(array $data, Request $request): void
    {
        $assignments = $this->validatedInitialAssignments($request, (int) $data['id_role']);
        $companionAssignments = $this->validatedInitialCompanionAssignments($request, (int) $data['id_role']);

        DB::transaction(function () use ($data, $assignments, $companionAssignments): void {
            $data['password'] = Hash::make(Str::random(40));
            $account = Pengguna::create($data);

            foreach ($assignments as $assignment) {
                RelasiGuruMapelKelas::create([
                    'id_pengguna' => $account->getKey(),
                    ...$assignment,
                ]);
            }

            foreach ($companionAssignments as $studentId) {
                RelasiPendampingSiswa::create([
                    'id_pengguna' => $account->getKey(),
                    'id_siswa' => $studentId,
                ]);
            }
        }, 3);
    }

    /**
     * Memperbarui akun dan pemetaan sesuai peran yang dipilih.
     *
     * @param  array{id_role: int, nama: string, username: string|null, email: string, nip: string|null, status_akun: string}  $data
     */
    private function updateNonParentUser(Pengguna $pengguna, array $data, Request $request): void
    {
        $assignments = $this->validatedInitialAssignments($request, (int) $data['id_role']);
        $companionAssignments = $this->validatedInitialCompanionAssignments($request, (int) $data['id_role']);

        DB::transaction(function () use ($pengguna, $data, $assignments, $companionAssignments): void {
            $account = Pengguna::query()->with('role')->lockForUpdate()->findOrFail($pengguna->getKey());

            if ($this->isLastActiveAdmin($account) && (
                $data['status_akun'] !== 'Aktif' || ! $this->isAdminRoleId((int) $data['id_role'])
            )) {
                throw ValidationException::withMessages([
                    $data['status_akun'] !== 'Aktif' ? 'status_akun' : 'id_role' => 'Akun admin aktif terakhir tidak dapat dinonaktifkan atau diubah perannya. Tambahkan atau aktifkan admin lain terlebih dahulu.',
                ]);
            }

            if ($account->relasiGuruMapelKelas()->exists() && ! $this->isMappingEducatorRole((int) $data['id_role'])) {
                throw ValidationException::withMessages([
                    'id_role' => 'Peran tidak dapat diubah karena akun ini masih memiliki pemetaan guru, kelas, atau mata pelajaran. Hapus pemetaannya terlebih dahulu.',
                ]);
            }

            $account->update($data);

            foreach ($assignments as $assignment) {
                RelasiGuruMapelKelas::query()->firstOrCreate([
                    'id_pengguna' => $account->getKey(),
                    ...$assignment,
                ]);
            }

            if ($this->isCompanionRole((int) $data['id_role'])) {
                $account->relasiPendampingSiswa()
                    ->when($companionAssignments !== [], fn ($relations) => $relations->whereNotIn('id_siswa', $companionAssignments))
                    ->when($companionAssignments === [], fn ($relations) => $relations)
                    ->delete();

                foreach ($companionAssignments as $studentId) {
                    RelasiPendampingSiswa::query()->firstOrCreate([
                        'id_pengguna' => $account->getKey(),
                        'id_siswa' => $studentId,
                    ]);
                }
            }
        }, 3);
    }

    /**
     * @return list<array{id_mapel: int, id_kelas: int}>
     */
    private function validatedInitialAssignments(Request $request, int $roleId): array
    {
        $data = $request->validate([
            'id_mapel' => ['nullable', 'integer', Rule::exists('mata_pelajaran', 'id_mapel')->where('status_mapel', 'Aktif')],
            'id_kelas' => ['nullable', 'array'],
            'id_kelas.*' => ['integer', 'distinct', Rule::exists('kelas', 'id_kelas')->where('status_kelas', 'Aktif')],
        ]);

        if (! $this->isSubjectTeacherRole($roleId)) {
            return [];
        }

        $hasSubject = filled($data['id_mapel'] ?? null);
        $classIds = collect($data['id_kelas'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $hasClass = $classIds->isNotEmpty();

        if ($hasSubject !== $hasClass) {
            throw ValidationException::withMessages([
                $hasSubject ? 'id_kelas' : 'id_mapel' => 'Pilih mata pelajaran dan kelas sekaligus, atau atur penugasan nanti pada halaman Pemetaan Guru.',
            ]);
        }

        $validClassCount = $hasSubject
            ? DB::table('relasi_kelas_mapel')
                ->where('id_mapel', $data['id_mapel'])
                ->whereIn('id_kelas', $classIds)
                ->count()
            : 0;

        if ($hasSubject && $validClassCount !== $classIds->count()) {
            throw ValidationException::withMessages([
                'id_kelas' => 'Salah satu kelas yang dipilih belum terkait dengan mata pelajaran tersebut. Atur kelas terkait pada menu Mata Pelajaran.',
            ]);
        }

        return $hasSubject
            ? $classIds->map(fn (int $classId) => [
                'id_mapel' => (int) $data['id_mapel'],
                'id_kelas' => $classId,
            ])->all()
            : [];
    }

    /** @return list<int> */
    private function validatedInitialCompanionAssignments(Request $request, int $roleId): array
    {
        $data = $request->validate([
            'id_siswa_pendamping' => ['nullable', 'array', 'max:2'],
            'id_siswa_pendamping.*' => ['integer', 'distinct', Rule::exists('siswa', 'id_siswa')->where('status_siswa', 'Aktif')],
        ]);

        if (! $this->isCompanionRole($roleId)) {
            return [];
        }

        return collect($data['id_siswa_pendamping'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function isStaffRole(int $roleId): bool
    {
        return $this->roleHasName($roleId, self::STAFF_ROLE_NAMES);
    }

    private function isSubjectTeacherRole(int $roleId): bool
    {
        return $this->roleHasName($roleId, ['Guru Mapel']);
    }

    private function isCompanionRole(int $roleId): bool
    {
        return $this->roleHasName($roleId, self::COMPANION_ROLE_NAMES);
    }

    private function isMappingEducatorRole(int $roleId): bool
    {
        return $this->roleHasName($roleId, self::MAPPING_EDUCATOR_ROLE_NAMES);
    }

    private function isParentRole(int $roleId): bool
    {
        return $this->roleHasName($roleId, ['Orang Tua']);
    }

    private function isLastActiveAdmin(Pengguna $pengguna): bool
    {
        if ($pengguna->status_akun !== 'Aktif' || ! $this->isAdminRole($pengguna)) {
            return false;
        }

        return ! Pengguna::query()
            ->where($pengguna->getKeyName(), '!=', $pengguna->getKey())
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->whereIn('nama_role', self::ADMIN_ROLE_NAMES))
            ->exists();
    }

    private function isAdminRole(Pengguna $pengguna): bool
    {
        return in_array($pengguna->role?->nama_role, self::ADMIN_ROLE_NAMES, true);
    }

    private function isAdminRoleId(int $roleId): bool
    {
        return $this->roleHasName($roleId, self::ADMIN_ROLE_NAMES);
    }

    /** @param list<string> $names */
    private function roleHasName(int $roleId, array $names): bool
    {
        return Role::query()->whereKey($roleId)->whereIn('nama_role', $names)->exists();
    }
}
