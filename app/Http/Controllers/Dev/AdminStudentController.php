<?php

namespace App\Http\Controllers\Dev;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminStudentController extends AdminController
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
     * @return array{id_kelas: int, nis: string, nama_siswa: string, kebutuhan_khusus: string, status_siswa: string}
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
            'kebutuhan_khusus' => ['required', 'string', 'max:150'],
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
}
