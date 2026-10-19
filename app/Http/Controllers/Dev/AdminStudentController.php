<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminStudentController extends Controller
{
    /**
     * @var list<string>
     */
    private const STATUSES = ['Aktif', 'Lulus', 'Pindah', 'Nonaktif'];

    public function index(Request $request): View
    {
        $nameOrder = $request->string('urut_nama')->toString() === 'za' ? 'za' : 'az';
        $query = Siswa::query()
            ->with('kelas')
            ->orderBy('nama_siswa', $nameOrder === 'za' ? 'desc' : 'asc')
            ->orderBy('id_siswa');

        if ($search = trim((string) $request->string('cari'))) {
            $query->where(function ($students) use ($search): void {
                $students
                    ->where('nama_siswa', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($kelasId = $request->integer('kelas')) {
            $query->where('id_kelas', $kelasId);
        }

        if (($status = $request->string('status')->toString()) && in_array($status, self::STATUSES, true)) {
            $query->where('status_siswa', $status);
        }

        return view('dev.admin.students.index', [
            'students' => $query->paginate($this->perPage($request))->withQueryString(),
            'classes' => Kelas::query()->orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'statuses' => self::STATUSES,
            'filters' => [
                ...$request->only(['cari', 'kelas', 'status']),
                'urut_nama' => $nameOrder,
            ],
            ...$this->viewContext(),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new Siswa, 'Tambah Data Siswa ABK');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedStudent($request);
        $photoPath = $this->storePhoto($request);

        if ($photoPath) {
            $data['foto'] = $photoPath;
        }

        try {
            Siswa::create($data);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }

        return redirect()->route('dev.admin.students.index')->with('success', 'Data siswa ABK berhasil ditambahkan.');
    }

    public function edit(Siswa $siswa): View
    {
        return $this->formView($siswa, 'Ubah Data Siswa ABK');
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $data = $this->validatedStudent($request, $siswa);
        $oldPhotoPath = $siswa->foto;
        $newPhotoPath = $this->storePhoto($request);

        if ($newPhotoPath) {
            $data['foto'] = $newPhotoPath;
        }

        try {
            $siswa->update($data);
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($newPhotoPath && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()->route('dev.admin.students.index')->with('success', 'Data siswa ABK berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        try {
            $blockers = DB::transaction(function () use ($siswa): array {
                $target = Siswa::query()->lockForUpdate()->findOrFail($siswa->getKey());
                $blockers = $this->deletionBlockers($target);

                if ($blockers === []) {
                    $target->delete();
                }

                return $blockers;
            }, 3);
        } catch (QueryException) {
            return redirect()->route('dev.admin.students.index')->with(
                'error',
                'Data siswa tidak dapat dihapus karena baru saja terhubung ke data lain. Gunakan status Nonaktif, Pindah, atau Lulus untuk menjaga riwayat data.'
            );
        }

        if ($blockers !== []) {
            return redirect()->route('dev.admin.students.index')->with(
                'error',
                "Data {$siswa->nama_siswa} tidak dapat dihapus karena ".implode(', ', $blockers).'. Gunakan perubahan status untuk menjaga riwayat data.'
            );
        }

        return redirect()->route('dev.admin.students.index')->with('success', "Data {$siswa->nama_siswa} berhasil dihapus permanen.");
    }

    private function formView(Siswa $siswa, string $title): View
    {
        return view('dev.admin.students.form', [
            'student' => $siswa,
            'classes' => Kelas::query()
                ->where(function ($classes) use ($siswa): void {
                    $classes->where('status_kelas', 'Aktif');

                    if ($siswa->exists) {
                        $classes->orWhere('id_kelas', $siswa->id_kelas);
                    }
                })
                ->orderBy('tingkat')
                ->orderBy('nama_kelas')
                ->get(),
            'statuses' => self::STATUSES,
            'title' => $title,
            ...$this->viewContext(),
        ]);
    }

    /**
     * @return array{id_kelas: int, nis: string, nama_siswa: string, kebutuhan_khusus: string|null, status_siswa: string}
     */
    private function validatedStudent(Request $request, ?Siswa $siswa = null): array
    {
        $availableClass = Rule::exists('kelas', 'id_kelas')->where(function ($classes) use ($siswa): void {
            $classes->where('status_kelas', 'Aktif');

            if ($siswa) {
                $classes->orWhere('id_kelas', $siswa->id_kelas);
            }
        });

        $data = $request->validate([
            'id_kelas' => ['required', 'integer', $availableClass],
            'nis' => ['required', 'string', 'max:30', Rule::unique('siswa', 'nis')->ignore($siswa, 'id_siswa')],
            'nama_siswa' => ['required', 'string', 'max:100'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'kebutuhan_khusus' => ['nullable', 'string', 'max:150'],
            'status_siswa' => ['required', Rule::in(self::STATUSES)],
        ]);

        unset($data['foto']);

        if ($siswa && $siswa->nis !== $data['nis'] && $siswa->relasiOrangTuaSiswa()
            ->whereHas('orangTua.pengguna', fn ($accounts) => $accounts->whereIn('username', [$siswa->nis, 'ortu_'.$siswa->nis]))
            ->exists()) {
            throw ValidationException::withMessages([
                'nis' => 'NIS tidak dapat diubah karena sedang digunakan sebagai username login Orang Tua/Wali. Kelola akun terkait terlebih dahulu.',
            ]);
        }

        return $data;
    }

    private function storePhoto(Request $request): ?string
    {
        return $request->hasFile('foto')
            ? $request->file('foto')->store('foto-siswa', 'public')
            : null;
    }

    /**
     * @return list<string>
     */
    private function deletionBlockers(Siswa $siswa): array
    {
        $dependencies = [
            [$siswa->catatanPerkembangan(), 'memiliki catatan perkembangan'],
            [$siswa->catatanPendampingan(), 'memiliki catatan pendampingan'],
            [$siswa->rekapPerkembangan(), 'memiliki rekap perkembangan'],
            [$siswa->relasiOrangTuaSiswa(), 'masih terhubung ke akun Orang Tua/Wali'],
            [$siswa->relasiPendampingSiswa(), 'masih dipetakan ke guru pendamping'],
        ];

        $blockers = [];

        foreach ($dependencies as [$relation, $message]) {
            if ($relation->exists()) {
                $blockers[] = $message;
            }
        }

        return $blockers;
    }

    private function perPage(Request $request): int
    {
        return in_array($request->integer('per_halaman'), [10, 30, 50, 100], true)
            ? $request->integer('per_halaman')
            : 10;
    }

    /**
     * @return array{role: string, profile: array{label: string, name: string, initials: string}, previewRoles: array<string, array{label: string, name: string, initials: string}>}
     */
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
