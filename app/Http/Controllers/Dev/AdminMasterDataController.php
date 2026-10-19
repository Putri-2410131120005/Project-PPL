<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMasterDataController extends Controller
{
    /** @var list<string> */
    private const CLASS_STATUSES = ['Aktif', 'Diarsipkan'];

    /** @var list<string> */
    private const SUBJECT_STATUSES = ['Aktif', 'Diarsipkan'];

    public function classesIndex(Request $request): View
    {
        $query = Kelas::query()
            ->with(['mataPelajaran' => fn ($subjects) => $subjects->orderBy('nama_mapel')])
            ->withCount(['siswa', 'relasiGuruMapelKelas'])
            ->orderBy('status_kelas')
            ->orderBy('tahun_ajaran')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas');

        if ($search = trim((string) $request->string('cari'))) {
            $query->where(function ($classes) use ($search): void {
                $classes
                    ->where('nama_kelas', 'like', "%{$search}%")
                    ->orWhere('tingkat', 'like', "%{$search}%")
                    ->orWhere('tahun_ajaran', 'like', "%{$search}%");
            });
        }

        if (($status = $request->string('status')->toString()) && in_array($status, self::CLASS_STATUSES, true)) {
            $query->where('status_kelas', $status);
        }

        return view('dev.admin.master-data.classes.index', [
            'classes' => $query->paginate($this->perPage($request))->withQueryString(),
            'classStatuses' => self::CLASS_STATUSES,
            'filters' => $request->only(['cari', 'status']),
            ...$this->viewContext(),
        ]);
    }

    public function classesCreate(): View
    {
        return $this->classForm(new Kelas, 'Tambah Kelas');
    }

    public function classesStore(Request $request): RedirectResponse
    {
        Kelas::create($this->validatedClass($request));

        return redirect()->route('dev.admin.classes.index')->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function classesEdit(Kelas $kelas): View
    {
        return $this->classForm($kelas, 'Ubah Kelas');
    }

    public function classesUpdate(Request $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($this->validatedClass($request, $kelas));

        return redirect()->route('dev.admin.classes.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function classesToggleArchive(Kelas $kelas): RedirectResponse
    {
        $newStatus = $kelas->status_kelas === 'Aktif' ? 'Diarsipkan' : 'Aktif';
        $kelas->update(['status_kelas' => $newStatus]);

        $message = $newStatus === 'Aktif'
            ? "Kelas {$kelas->nama_kelas} berhasil diaktifkan kembali."
            : "Kelas {$kelas->nama_kelas} berhasil diarsipkan. Riwayat siswa dan pemetaan tetap tersimpan.";

        return redirect()->route('dev.admin.classes.index')->with('success', $message);
    }

    public function classesDestroy(Kelas $kelas): RedirectResponse
    {
        try {
            $blockers = $this->classDeletionBlockers($kelas);

            if ($blockers !== []) {
                return redirect()->route('dev.admin.classes.index')->with(
                    'error',
                    "Kelas {$kelas->nama_kelas} tidak dapat dihapus karena ".implode(', ', $blockers).'.'
                );
            }

            $kelas->delete();
        } catch (QueryException) {
            return redirect()->route('dev.admin.classes.index')->with(
                'error',
                'Kelas tidak dapat dihapus karena baru saja digunakan oleh data lain. Hapus atau pindahkan relasinya terlebih dahulu.'
            );
        }

        return redirect()->route('dev.admin.classes.index')->with('success', "Kelas {$kelas->nama_kelas} berhasil dihapus.");
    }

    public function subjectsIndex(Request $request): View
    {
        $query = MataPelajaran::query()
            ->with(['kelas' => fn ($classes) => $classes->orderBy('tingkat')->orderBy('nama_kelas')])
            ->withCount(['catatanPerkembangan', 'relasiGuruMapelKelas'])
            ->orderBy('status_mapel')
            ->orderBy('nama_mapel');

        if ($search = trim((string) $request->string('cari'))) {
            $query->where('nama_mapel', 'like', "%{$search}%");
        }

        if (($status = $request->string('status')->toString()) && in_array($status, self::SUBJECT_STATUSES, true)) {
            $query->where('status_mapel', $status);
        }

        return view('dev.admin.master-data.subjects.index', [
            'subjects' => $query->paginate($this->perPage($request))->withQueryString(),
            'subjectStatuses' => self::SUBJECT_STATUSES,
            'filters' => $request->only(['cari', 'status']),
            ...$this->viewContext(),
        ]);
    }

    public function subjectsCreate(): View
    {
        return $this->subjectForm(new MataPelajaran, 'Tambah Mata Pelajaran');
    }

    public function subjectsStore(Request $request): RedirectResponse
    {
        $data = $this->validatedSubject($request);
        $classIds = $data['id_kelas'];
        unset($data['id_kelas']);

        DB::transaction(function () use ($data, $classIds): void {
            $subject = MataPelajaran::create($data);
            $subject->kelas()->sync($classIds);
        }, 3);

        return redirect()->route('dev.admin.subjects.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function subjectsEdit(MataPelajaran $mataPelajaran): View
    {
        return $this->subjectForm($mataPelajaran, 'Ubah Mata Pelajaran');
    }

    public function subjectsUpdate(Request $request, MataPelajaran $mataPelajaran): RedirectResponse
    {
        $data = $this->validatedSubject($request, $mataPelajaran);
        $classIds = $data['id_kelas'];
        unset($data['id_kelas']);

        DB::transaction(function () use ($mataPelajaran, $data, $classIds): void {
            $mataPelajaran->update($data);
            $mataPelajaran->kelas()->sync($classIds);
        }, 3);

        return redirect()->route('dev.admin.subjects.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function subjectsToggleArchive(MataPelajaran $mataPelajaran): RedirectResponse
    {
        $newStatus = $mataPelajaran->status_mapel === 'Aktif' ? 'Diarsipkan' : 'Aktif';
        $mataPelajaran->update(['status_mapel' => $newStatus]);

        $message = $newStatus === 'Aktif'
            ? "Mata pelajaran {$mataPelajaran->nama_mapel} berhasil diaktifkan kembali."
            : "Mata pelajaran {$mataPelajaran->nama_mapel} berhasil diarsipkan. Catatan dan pemetaan lama tetap tersimpan.";

        return redirect()->route('dev.admin.subjects.index')->with('success', $message);
    }

    public function subjectsDestroy(MataPelajaran $mataPelajaran): RedirectResponse
    {
        try {
            $blockers = $this->subjectDeletionBlockers($mataPelajaran);

            if ($blockers !== []) {
                return redirect()->route('dev.admin.subjects.index')->with(
                    'error',
                    "Mata pelajaran {$mataPelajaran->nama_mapel} tidak dapat dihapus karena ".implode(', ', $blockers).'.'
                );
            }

            $mataPelajaran->delete();
        } catch (QueryException) {
            return redirect()->route('dev.admin.subjects.index')->with(
                'error',
                'Mata pelajaran tidak dapat dihapus karena baru saja digunakan oleh data lain. Hapus atau pindahkan relasinya terlebih dahulu.'
            );
        }

        return redirect()->route('dev.admin.subjects.index')->with('success', "Mata pelajaran {$mataPelajaran->nama_mapel} berhasil dihapus.");
    }

    private function classForm(Kelas $kelas, string $title): View
    {
        return view('dev.admin.master-data.classes.form', [
            'kelas' => $kelas,
            'classStatuses' => self::CLASS_STATUSES,
            'title' => $title,
            ...$this->viewContext(),
        ]);
    }

    private function subjectForm(MataPelajaran $mataPelajaran, string $title): View
    {
        $linkedClassIds = $mataPelajaran->exists
            ? $mataPelajaran->kelas()->pluck('kelas.id_kelas')
            : collect();

        return view('dev.admin.master-data.subjects.form', [
            'mataPelajaran' => $mataPelajaran,
            'classes' => Kelas::query()
                ->where(function ($classes) use ($linkedClassIds): void {
                    $classes->where('status_kelas', 'Aktif');

                    if ($linkedClassIds->isNotEmpty()) {
                        $classes->orWhereIn('id_kelas', $linkedClassIds);
                    }
                })
                ->orderBy('tingkat')
                ->orderBy('nama_kelas')
                ->get(),
            'linkedClassIds' => $linkedClassIds,
            'subjectStatuses' => self::SUBJECT_STATUSES,
            'title' => $title,
            ...$this->viewContext(),
        ]);
    }

    /**
     * @return array{nama_kelas: string, tingkat: string, tahun_ajaran: string, status_kelas: string}
     */
    private function validatedClass(Request $request, ?Kelas $kelas = null): array
    {
        $uniqueClass = Rule::unique('kelas', 'nama_kelas')->where(
            fn ($query) => $query
                ->where('tingkat', $request->input('tingkat'))
                ->where('tahun_ajaran', $request->input('tahun_ajaran'))
        );

        if ($kelas) {
            $uniqueClass->ignore($kelas->getKey(), $kelas->getKeyName());
        }

        $data = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', $uniqueClass],
            'tingkat' => ['required', 'string', 'max:10'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'status_kelas' => ['nullable', Rule::in(self::CLASS_STATUSES)],
        ], [
            'nama_kelas.unique' => 'Kelas dengan tingkat dan tahun ajaran yang sama sudah ada.',
        ]);

        $data['status_kelas'] ??= $kelas?->status_kelas ?? 'Aktif';

        return $data;
    }

    /**
     * @return array{nama_mapel: string, status_mapel: string, id_kelas: list<int>}
     */
    private function validatedSubject(Request $request, ?MataPelajaran $mataPelajaran = null): array
    {
        $uniqueSubject = Rule::unique('mata_pelajaran', 'nama_mapel');

        if ($mataPelajaran) {
            $uniqueSubject->ignore($mataPelajaran->getKey(), $mataPelajaran->getKeyName());
        }

        $linkedClassIds = $mataPelajaran?->kelas()->pluck('kelas.id_kelas')->all() ?? [];
        $availableClass = Rule::exists('kelas', 'id_kelas')->where(function ($classes) use ($linkedClassIds): void {
            $classes->where('status_kelas', 'Aktif');

            if ($linkedClassIds !== []) {
                $classes->orWhereIn('id_kelas', $linkedClassIds);
            }
        });

        $data = $request->validate([
            'nama_mapel' => ['required', 'string', 'max:100', $uniqueSubject],
            'status_mapel' => ['nullable', Rule::in(self::SUBJECT_STATUSES)],
            'id_kelas' => ['required', 'array', 'min:1'],
            'id_kelas.*' => ['integer', 'distinct', $availableClass],
        ], [
            'nama_mapel.unique' => 'Mata pelajaran tersebut sudah tersedia.',
            'id_kelas.required' => 'Pilih minimal satu kelas yang menggunakan mata pelajaran ini.',
            'id_kelas.min' => 'Pilih minimal satu kelas yang menggunakan mata pelajaran ini.',
        ]);

        $data['status_mapel'] ??= $mataPelajaran?->status_mapel ?? 'Aktif';

        return $data;
    }

    /**
     * @return list<string>
     */
    private function classDeletionBlockers(Kelas $kelas): array
    {
        $blockers = [];

        if ($kelas->siswa()->exists()) {
            $blockers[] = 'masih memiliki siswa';
        }

        if ($kelas->relasiGuruMapelKelas()->exists()) {
            $blockers[] = 'masih dipetakan ke guru atau mata pelajaran';
        }

        return $blockers;
    }

    /**
     * @return list<string>
     */
    private function subjectDeletionBlockers(MataPelajaran $mataPelajaran): array
    {
        $blockers = [];

        if ($mataPelajaran->catatanPerkembangan()->exists()) {
            $blockers[] = 'sudah memiliki catatan perkembangan';
        }

        if ($mataPelajaran->relasiGuruMapelKelas()->exists()) {
            $blockers[] = 'masih dipetakan ke guru atau kelas';
        }

        return $blockers;
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
