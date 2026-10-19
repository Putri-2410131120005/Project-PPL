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
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Development Admin mapping screens.
 *
 * Each mapping is deliberately managed as an independent record. Existing
 * foreign keys and composite unique indexes remain the source of truth; this
 * controller only validates them before changing a relationship.
 */
class AdminMappingController extends Controller
{
    public function guruMapelKelasIndex(Request $request): View
    {
        $query = RelasiGuruMapelKelas::query()
            ->with(['guru.role', 'mataPelajaran', 'kelas'])
            ->orderByDesc('id_relasi_guru_mapel');

        if ($search = trim((string) $request->string('cari'))) {
            $query->where(function ($mappings) use ($search): void {
                $mappings
                    ->whereHas('guru', fn ($users) => $users->where('nama', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('mataPelajaran', fn ($subjects) => $subjects->where('nama_mapel', 'like', "%{$search}%"))
                    ->orWhereHas('kelas', fn ($classes) => $classes
                        ->where('nama_kelas', 'like', "%{$search}%")
                        ->orWhere('tingkat', 'like', "%{$search}%")
                        ->orWhere('tahun_ajaran', 'like', "%{$search}%"));
            });
        }

        return view('dev.admin.mappings.guru-mapel-kelas', [
            'mappings' => $query->paginate($this->perPage($request))->withQueryString(),
            'gurus' => $this->educatorOptions(),
            'mataPelajaran' => MataPelajaran::query()
                ->where('status_mapel', 'Aktif')
                ->orderBy('nama_mapel')
                ->get(),
            'kelas' => Kelas::query()->where('status_kelas', 'Aktif')->orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'filters' => $request->only('cari'),
            ...$this->viewContext(),
        ]);
    }

    public function guruMapelKelasStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_pengguna' => ['required', 'integer', Rule::exists('pengguna', 'id_pengguna')],
            'id_mapel' => ['required', 'integer', Rule::exists('mata_pelajaran', 'id_mapel')->where('status_mapel', 'Aktif')],
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')->where('status_kelas', 'Aktif')],
        ]);

        if (! $this->isEligibleEducator($data['id_pengguna'])) {
            throw ValidationException::withMessages([
                'id_pengguna' => 'Pilih akun Guru Mapel, Tim Inklusi, atau GPK yang masih aktif.',
            ]);
        }

        if (RelasiGuruMapelKelas::query()->where($data)->exists()) {
            throw ValidationException::withMessages([
                'id_pengguna' => 'Pemetaan guru, mata pelajaran, dan kelas tersebut sudah ada.',
            ]);
        }

        try {
            RelasiGuruMapelKelas::create($data);
        } catch (QueryException) {
            // The composite unique index still protects a concurrent request.
            throw ValidationException::withMessages([
                'id_pengguna' => 'Pemetaan guru, mata pelajaran, dan kelas tersebut sudah ada atau datanya baru berubah.',
            ]);
        }

        return redirect()
            ->route('dev.admin.mappings.guru-mapel-kelas.index')
            ->with('success', 'Pemetaan guru, kelas, dan mata pelajaran berhasil ditambahkan.');
    }

    public function guruMapelKelasDestroy(RelasiGuruMapelKelas $mapping): RedirectResponse
    {
        $mapping->delete();

        return redirect()
            ->route('dev.admin.mappings.guru-mapel-kelas.index')
            ->with('success', 'Pemetaan guru, kelas, dan mata pelajaran dihapus.');
    }

    public function pendampingSiswaIndex(Request $request): View
    {
        $query = RelasiPendampingSiswa::query()
            ->with(['pendamping.role', 'siswa.kelas'])
            ->orderByDesc('id_relasi_pendamping');

        if ($search = trim((string) $request->string('cari'))) {
            $query->where(function ($mappings) use ($search): void {
                $mappings
                    ->whereHas('pendamping', fn ($users) => $users
                        ->where('nama', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%"))
                    ->orWhereHas('siswa', fn ($students) => $students
                        ->where('nama_siswa', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%"));
            });
        }

        return view('dev.admin.mappings.pendamping-siswa', [
            'mappings' => $query->paginate($this->perPage($request))->withQueryString(),
            'pendamping' => $this->pendampingOptions(),
            'siswa' => $this->activeStudentOptions(),
            'filters' => $request->only('cari'),
            ...$this->viewContext(),
        ]);
    }

    public function pendampingSiswaStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_pengguna' => ['required', 'integer', Rule::exists('pengguna', 'id_pengguna')],
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
        ]);

        if (! $this->isEligiblePendamping($data['id_pengguna'])) {
            throw ValidationException::withMessages([
                'id_pengguna' => 'Pilih akun GPK yang masih aktif sebagai guru pendamping.',
            ]);
        }

        if (! $this->isActiveStudent($data['id_siswa'])) {
            throw ValidationException::withMessages([
                'id_siswa' => 'Pemetaan baru hanya dapat dibuat untuk siswa dengan status Aktif.',
            ]);
        }

        if (RelasiPendampingSiswa::query()->where($data)->exists()) {
            throw ValidationException::withMessages([
                'id_pengguna' => 'Guru pendamping tersebut sudah dipetakan ke siswa ini.',
            ]);
        }

        try {
            RelasiPendampingSiswa::create($data);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'id_pengguna' => 'Pemetaan guru pendamping sudah ada atau datanya baru berubah.',
            ]);
        }

        return redirect()
            ->route('dev.admin.mappings.pendamping-siswa.index')
            ->with('success', 'Pemetaan siswa dengan guru pendamping berhasil ditambahkan.');
    }

    public function pendampingSiswaDestroy(RelasiPendampingSiswa $mapping): RedirectResponse
    {
        $mapping->delete();

        return redirect()
            ->route('dev.admin.mappings.pendamping-siswa.index')
            ->with('success', 'Pemetaan siswa dengan guru pendamping dihapus.');
    }

    public function orangTuaSiswaIndex(Request $request): View
    {
        $query = RelasiOrangTuaSiswa::query()
            ->with(['orangTua.pengguna', 'siswa.kelas'])
            ->orderByDesc('id_relasi_ortu');

        if ($search = trim((string) $request->string('cari'))) {
            $query->where(function ($mappings) use ($search): void {
                $mappings
                    ->whereHas('orangTua.pengguna', fn ($users) => $users
                        ->where('nama', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%"))
                    ->orWhereHas('siswa', fn ($students) => $students
                        ->where('nama_siswa', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%"));
            });
        }

        return view('dev.admin.mappings.orang-tua-siswa', [
            'mappings' => $query->paginate($this->perPage($request))->withQueryString(),
            'orangTua' => $this->parentOptions(),
            'siswa' => $this->activeStudentOptions(),
            'filters' => $request->only('cari'),
            ...$this->viewContext(),
        ]);
    }

    public function orangTuaSiswaStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_orang_tua' => ['required', 'integer', Rule::exists('orang_tua', 'id_orang_tua')],
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
            'hubungan' => ['required', 'string', Rule::in(['Ayah', 'Ibu', 'Wali'])],
        ]);

        if (! $this->isEligibleParent($data['id_orang_tua'])) {
            throw ValidationException::withMessages([
                'id_orang_tua' => 'Pilih akun Orang Tua/Wali yang masih aktif.',
            ]);
        }

        if (! $this->isActiveStudent($data['id_siswa'])) {
            throw ValidationException::withMessages([
                'id_siswa' => 'Pemetaan baru hanya dapat dibuat untuk siswa dengan status Aktif.',
            ]);
        }

        if (RelasiOrangTuaSiswa::query()
            ->where('id_orang_tua', $data['id_orang_tua'])
            ->where('id_siswa', $data['id_siswa'])
            ->exists()) {
            throw ValidationException::withMessages([
                'id_orang_tua' => 'Orang Tua/Wali tersebut sudah terhubung ke siswa ini.',
            ]);
        }

        try {
            RelasiOrangTuaSiswa::create($data);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'id_orang_tua' => 'Pemetaan Orang Tua/Wali sudah ada atau datanya baru berubah.',
            ]);
        }

        return redirect()
            ->route('dev.admin.mappings.orang-tua-siswa.index')
            ->with('success', 'Pemetaan siswa dengan Orang Tua/Wali berhasil ditambahkan.');
    }

    public function orangTuaSiswaDestroy(RelasiOrangTuaSiswa $mapping): RedirectResponse
    {
        $mapping->load(['orangTua.pengguna', 'siswa']);

        if (in_array($mapping->orangTua?->pengguna?->username, [
            $mapping->siswa?->nis,
            $mapping->siswa ? 'ortu_'.$mapping->siswa->nis : null,
        ], true)) {
            return redirect()
                ->route('dev.admin.mappings.orang-tua-siswa.index')
                ->with('error', 'Pemetaan ini tidak dapat dihapus karena NIS siswa tersebut masih dipakai sebagai username login Orang Tua/Wali. Hapus akun atau gunakan akun yang sesuai terlebih dahulu.');
        }

        $mapping->delete();

        return redirect()
            ->route('dev.admin.mappings.orang-tua-siswa.index')
            ->with('success', 'Pemetaan siswa dengan Orang Tua/Wali dihapus.');
    }

    /**
     * Informational only. The database has roles but no permission matrix, so
     * this screen intentionally reports assignments without inventing access
     * rows that do not exist in the schema.
     */
    public function accessRights(Request $request): View
    {
        return view('dev.admin.mappings.access-rights', [
            'roles' => Role::query()
                ->withCount('pengguna')
                ->orderBy('nama_role')
                ->paginate($this->perPage($request))
                ->withQueryString(),
            ...$this->viewContext(),
        ]);
    }

    private function educatorOptions()
    {
        return Pengguna::query()
            ->with('role')
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->whereIn('nama_role', ['Guru Mapel', 'Tim Inklusi', 'GPK']))
            ->orderBy('nama')
            ->get();
    }

    private function pendampingOptions()
    {
        return Pengguna::query()
            ->with('role')
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->where('nama_role', 'GPK'))
            ->orderBy('nama')
            ->get();
    }

    private function parentOptions()
    {
        return OrangTua::query()
            ->with('pengguna')
            ->whereHas('pengguna', fn ($query) => $query
                ->where('status_akun', 'Aktif')
                ->whereHas('role', fn ($roles) => $roles->where('nama_role', 'Orang Tua')))
            ->orderBy('id_orang_tua')
            ->get();
    }

    private function activeStudentOptions()
    {
        return Siswa::query()
            ->with('kelas')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_siswa')
            ->get();
    }

    private function isEligibleEducator(int $penggunaId): bool
    {
        return Pengguna::query()
            ->whereKey($penggunaId)
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->whereIn('nama_role', ['Guru Mapel', 'Tim Inklusi', 'GPK']))
            ->exists();
    }

    private function isEligiblePendamping(int $penggunaId): bool
    {
        return Pengguna::query()
            ->whereKey($penggunaId)
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($query) => $query->where('nama_role', 'GPK'))
            ->exists();
    }

    private function isEligibleParent(int $orangTuaId): bool
    {
        return OrangTua::query()
            ->whereKey($orangTuaId)
            ->whereHas('pengguna', fn ($query) => $query
                ->where('status_akun', 'Aktif')
                ->whereHas('role', fn ($roles) => $roles->where('nama_role', 'Orang Tua')))
            ->exists();
    }

    private function isActiveStudent(int $siswaId): bool
    {
        return Siswa::query()
            ->whereKey($siswaId)
            ->where('status_siswa', 'Aktif')
            ->exists();
    }

    private function perPage(Request $request): int
    {
        return in_array($request->integer('per_halaman'), [10, 30, 50, 100], true)
            ? $request->integer('per_halaman')
            : 10;
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
