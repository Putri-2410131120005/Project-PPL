<?php

namespace App\Http\Controllers\Dev;

use App\Models\RelasiGuruMapelKelas;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMappingController extends AdminController
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
            'filters' => $request->only('cari'),
            ...$this->viewContext(),
        ]);
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
            'filters' => $request->only('cari'),
            ...$this->viewContext(),
        ]);
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
            'filters' => $request->only('cari'),
            ...$this->viewContext(),
        ]);
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

    // Menampilkan ringkasan peran tanpa membuat data hak akses baru.
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
}
