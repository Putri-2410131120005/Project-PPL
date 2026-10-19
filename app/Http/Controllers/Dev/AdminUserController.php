<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Role;
use App\Models\Siswa;
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

class AdminUserController extends Controller
{
    /** @var list<string> */
    private const STAFF_ROLE_NAMES = ['Guru Mapel', 'Tim Inklusi', 'GPK', 'Kepala Sekolah'];

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

    public function destroy(Pengguna $pengguna): RedirectResponse
    {
        try {
            $blockers = DB::transaction(function () use ($pengguna): array {
                $target = Pengguna::query()
                    ->with('role')
                    ->lockForUpdate()
                    ->findOrFail($pengguna->getKey());

                $parentProfile = OrangTua::query()
                    ->where('id_pengguna', $target->getKey())
                    ->lockForUpdate()
                    ->first();

                $blockers = $this->deletionBlockers($target, $parentProfile);

                if ($blockers !== []) {
                    return $blockers;
                }

                // Relasi siswa adalah bagian dari profil Orang Tua/Wali dan tidak
                // menyimpan riwayat pembelajaran. Hapus atomik bersama akun agar
                // admin dapat membatalkan akun yang dibuat untuk siswa yang keliru.
                $parentProfile?->relasiSiswa()->delete();
                $parentProfile?->delete();
                $target->delete();

                return [];
            }, 3);
        } catch (QueryException) {
            return redirect()->route('dev.admin.users.index')->with(
                'error',
                'Akun tidak dapat dihapus karena baru saja terhubung ke data lain. Gunakan Nonaktifkan untuk menjaga riwayat data.'
            );
        }

        if ($blockers !== []) {
            return redirect()->route('dev.admin.users.index')->with(
                'error',
                "Akun {$pengguna->nama} tidak dapat dihapus karena ".implode(', ', $blockers).'. Gunakan Nonaktifkan untuk menjaga riwayat data.'
            );
        }

        return redirect()->route('dev.admin.users.index')->with('success', "Akun {$pengguna->nama} berhasil dihapus permanen.");
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
            'classes' => Kelas::query()->where('status_kelas', 'Aktif')->orderBy('tingkat')->orderBy('nama_kelas')->get(),
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

        if ($this->isEducatorRole((int) $data['id_role']) && blank($data['nip'] ?? null)) {
            throw ValidationException::withMessages([
                'nip' => 'NIP wajib diisi untuk Guru Mapel, Tim Inklusi, GPK, dan Kepala Sekolah.',
            ]);
        }

        if (! $this->isEducatorRole((int) $data['id_role'])) {
            $data['nip'] = null;
        }

        return $data;
    }

    /**
     * Create an account and, when supplied for a Guru Mapel, its first teaching
     * assignment as one atomic change. Further assignments remain managed from
     * the dedicated Pemetaan page.
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
     * Keep account edits safe and consistent with the selected role. An
     * existing mapel/class assignment is only added when the form supplies a
     * complete new combination; it is never silently removed by an account
     * edit.
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

    private function isEducatorRole(int $roleId): bool
    {
        return $this->isStaffRole($roleId);
    }

    private function isStaffRole(int $roleId): bool
    {
        return Role::query()->whereKey($roleId)->whereIn('nama_role', self::STAFF_ROLE_NAMES)->exists();
    }

    private function isSubjectTeacherRole(int $roleId): bool
    {
        return Role::query()->whereKey($roleId)->where('nama_role', 'Guru Mapel')->exists();
    }

    private function isCompanionRole(int $roleId): bool
    {
        return Role::query()->whereKey($roleId)->whereIn('nama_role', ['GPK', 'Tim Inklusi'])->exists();
    }

    private function isMappingEducatorRole(int $roleId): bool
    {
        return Role::query()
            ->whereKey($roleId)
            ->whereIn('nama_role', ['Guru Mapel', 'Tim Inklusi', 'GPK'])
            ->exists();
    }

    private function perPage(Request $request): int
    {
        return in_array($request->integer('per_halaman'), [10, 30, 50, 100], true)
            ? $request->integer('per_halaman')
            : 10;
    }

    private function isParentRole(int $roleId): bool
    {
        return Role::query()->whereKey($roleId)->where('nama_role', 'Orang Tua')->exists();
    }

    /**
     * @return list<string>
     */
    private function deletionBlockers(Pengguna $pengguna, ?OrangTua $parentProfile): array
    {
        $blockers = [];

        if ($this->isLastActiveAdmin($pengguna)) {
            $blockers[] = 'merupakan akun admin aktif terakhir';
        }

        if ($parentProfile?->relasiSiswa()->exists()) {
            $blockers[] = 'masih terhubung ke data siswa';
        }

        $dependencies = [
            [$pengguna->catatanPerkembangan(), 'memiliki catatan perkembangan'],
            [$pengguna->catatanPendampingan(), 'memiliki catatan pendampingan'],
            [$pengguna->verifikasiDilakukan(), 'memiliki riwayat verifikasi'],
            [$pengguna->publikasiDilakukan(), 'memiliki riwayat publikasi'],
            [$pengguna->rekapDibuat(), 'memiliki rekap perkembangan'],
            [$pengguna->laporanDibuat(), 'memiliki laporan perkembangan'],
            [$pengguna->relasiGuruMapelKelas(), 'masih dipetakan ke kelas atau mata pelajaran'],
            [$pengguna->relasiPendampingSiswa(), 'masih dipetakan sebagai pendamping siswa'],
        ];

        foreach ($dependencies as [$relation, $message]) {
            if ($relation->exists()) {
                $blockers[] = $message;
            }
        }

        return $blockers;
    }

    private function isLastActiveAdmin(Pengguna $pengguna): bool
    {
        if ($pengguna->status_akun !== 'Aktif' || ! $this->isAdminRole($pengguna)) {
            return false;
        }

        return ! Pengguna::query()
            ->where($pengguna->getKeyName(), '!=', $pengguna->getKey())
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->whereIn('nama_role', ['Admin', 'Admin / Pengelola']))
            ->exists();
    }

    private function isAdminRole(Pengguna $pengguna): bool
    {
        return in_array($pengguna->role?->nama_role, ['Admin', 'Admin / Pengelola'], true);
    }

    private function isAdminRoleId(int $roleId): bool
    {
        return Role::query()
            ->whereKey($roleId)
            ->whereIn('nama_role', ['Admin', 'Admin / Pengelola'])
            ->exists();
    }

    private function viewContext(): array
    {
        return [
            'role' => 'admin',
            'profile' => ['label' => 'Admin / Pengelola', 'name' => 'Administrator', 'initials' => 'AD'],
            'previewRoles' => [
                'admin' => ['label' => 'Admin / Pengelola', 'name' => 'Administrator', 'initials' => 'AD'],
                'guru-mapel' => ['label' => 'Guru Mata Pelajaran', 'name' => 'Guru Mata Pelajaran', 'initials' => 'GM'],
                'tim-inklusi' => ['label' => 'Tim Inklusi', 'name' => 'Tim Inklusi', 'initials' => 'TI'],
                'gpk' => ['label' => 'GPK', 'name' => 'Guru Pendamping Khusus', 'initials' => 'GP'],
                'kepala-sekolah' => ['label' => 'Kepala Sekolah', 'name' => 'Kepala Sekolah', 'initials' => 'KS'],
                'orang-tua' => ['label' => 'Orang Tua', 'name' => 'Orang Tua/Wali', 'initials' => 'OT'],
            ],
        ];
    }
}
