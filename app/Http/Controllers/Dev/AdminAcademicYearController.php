<?php

namespace App\Http\Controllers\Dev;

use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAcademicYearController extends AdminController
{
    public function index(Request $request): View
    {
        $query = TahunAjaran::query()->withCount('kelas')->orderByDesc('mulai_ganjil');
        if ($search = trim((string) $request->string('cari'))) {
            $query->where('nama', 'like', "%{$search}%");
        }

        return view('dev.admin.academic-years.index', [
            'academicYears' => $query->paginate($this->perPage($request))->withQueryString(),
            'filters' => $request->only('cari'),
            ...$this->viewContext(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new TahunAjaran, 'Tambah Tahun Ajaran');
    }

    public function store(Request $request): RedirectResponse
    {
        TahunAjaran::query()->create($this->validatedData($request));

        return redirect()->route('dev.admin.academic-years.index')
            ->with('success', 'Tahun ajaran berhasil ditambahkan. Aktifkan periode saat sekolah siap menggunakannya.');
    }

    public function edit(TahunAjaran $tahunAjaran): View
    {
        return $this->form($tahunAjaran, 'Ubah Tahun Ajaran');
    }

    public function update(Request $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $data = $this->validatedData($request, $tahunAjaran);
        DB::transaction(function () use ($tahunAjaran, $data): void {
            $tahunAjaran->update($data);
            $tahunAjaran->kelas()->update(['tahun_ajaran' => $tahunAjaran->nama]);
        }, 3);

        return redirect()->route('dev.admin.academic-years.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function activate(TahunAjaran $tahunAjaran): RedirectResponse
    {
        DB::transaction(function () use ($tahunAjaran): void {
            TahunAjaran::query()->whereKeyNot($tahunAjaran->getKey())->get()->each(function (TahunAjaran $year) use ($tahunAjaran): void {
                $year->update(['status' => $year->selesai_genap->lt($tahunAjaran->mulai_ganjil) ? 'Selesai' : 'Akan Datang']);
            });
            $tahunAjaran->update(['status' => 'Aktif']);
        }, 3);

        return back()->with('success', "Tahun ajaran {$tahunAjaran->nama} sekarang menjadi periode aktif dan pilihan utama semua modul.");
    }

    private function form(TahunAjaran $tahunAjaran, string $title): View
    {
        return view('dev.admin.academic-years.form', [
            'academicYear' => $tahunAjaran,
            'title' => $title,
            ...$this->viewContext(),
        ]);
    }

    private function validatedData(Request $request, ?TahunAjaran $tahunAjaran = null): array
    {
        $uniqueName = Rule::unique('tahun_ajaran', 'nama');
        if ($tahunAjaran) {
            $uniqueName->ignore($tahunAjaran->getKey(), $tahunAjaran->getKeyName());
        }

        $data = $request->validate([
            'nama' => ['required', 'regex:/^(\d{4})\/(\d{4})$/', $uniqueName],
            'mulai_ganjil' => ['required', 'date'],
            'selesai_ganjil' => ['required', 'date', 'after_or_equal:mulai_ganjil'],
            'mulai_genap' => ['required', 'date', 'after:selesai_ganjil'],
            'selesai_genap' => ['required', 'date', 'after_or_equal:mulai_genap'],
        ], [
            'nama.regex' => 'Gunakan format tahun ajaran YYYY/YYYY, misalnya 2026/2027.',
            'nama.unique' => 'Tahun ajaran tersebut sudah tersedia.',
        ]);

        [$startYear, $endYear] = array_map('intval', explode('/', $data['nama']));
        if ($endYear !== $startYear + 1) {
            return throw \Illuminate\Validation\ValidationException::withMessages([
                'nama' => 'Tahun kedua harus tepat satu tahun setelah tahun pertama.',
            ]);
        }

        $data['status'] = $tahunAjaran?->status ?? 'Akan Datang';

        return $data;
    }
}
