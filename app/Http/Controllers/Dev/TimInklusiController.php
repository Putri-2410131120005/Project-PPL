<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\Pengguna;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TimInklusiController extends Controller
{
    public function dashboard(Request $request): View
    {
        $pendamping = $this->pendamping($request);
        $catatan = CatatanPendampingan::query()->where('id_pembuat', $pendamping->getKey());

        return view('dev.tim-inklusi.dashboard', array_merge($this->viewContext($pendamping), [
            'stats' => [
                'students' => $this->assignedStudents($pendamping)->count(),
                'pending' => (clone $catatan)->where('status_pengajuan', 'Dikirim')->count(),
                'verified' => (clone $catatan)->where('status_pengajuan', 'Terverifikasi')->count(),
            ],
            'latestNotes' => $catatan->with(['siswa.kelas'])
                ->latest('tanggal_catatan')
                ->latest('id_catatan_pendampingan')
                ->limit(5)
                ->get(),
        ]));
    }

    public function students(Request $request): View
    {
        $pendamping = $this->pendamping($request);
        $perPage = $this->perPage($request);
        $search = trim((string) $request->query('cari'));
        $level = strtoupper((string) $request->query('tingkat'));
        $level = in_array($level, ['X', 'XI', 'XII'], true) ? $level : '';

        $students = $this->assignedStudents($pendamping)
            ->with('kelas')
            ->withMax([
                'catatanPendampingan as catatan_terakhir' => fn ($query) => $query
                    ->where('id_pembuat', $pendamping->getKey()),
            ], 'tanggal_catatan')
            ->when($search !== '', fn ($query) => $query->where('nama_siswa', 'like', "%{$search}%"))
            ->when($level !== '', fn ($query) => $query->whereHas('kelas', fn ($classes) => $classes->where('tingkat', $level)))
            ->orderBy('nama_siswa')
            ->paginate($perPage)
            ->withQueryString();

        return view('dev.tim-inklusi.students.index', array_merge($this->viewContext($pendamping), [
            'students' => $students,
            'filters' => ['cari' => $search, 'tingkat' => $level],
        ]));
    }

    public function student(Request $request, Siswa $siswa): View
    {
        $pendamping = $this->pendamping($request);
        $this->ensureAssigned($pendamping, $siswa);
        $history = $this->studentHistory($request, $pendamping, $siswa);

        return view('dev.tim-inklusi.students.show', array_merge($this->viewContext($pendamping), compact('siswa', 'history')));
    }

    public function recommendation(Request $request, Siswa $siswa): View
    {
        $pendamping = $this->pendamping($request);
        $this->ensureAssigned($pendamping, $siswa);

        $recommendations = collect();
        CatatanPendampingan::query()
            ->where('id_siswa', $siswa->getKey())
            ->where('status_pengajuan', 'Terverifikasi')
            ->latest('tanggal_catatan')
            ->limit(10)
            ->get()
            ->each(function ($note) use ($recommendations) {
                if (filled($note->tindak_lanjut)) {
                    $recommendations->push(['title' => 'Tindak Lanjut Pendampingan', 'body' => $note->tindak_lanjut, 'date' => $note->tanggal_catatan]);
                }
                if (filled($note->kendala)) {
                    $recommendations->push(['title' => 'Kebutuhan Dukungan', 'body' => $note->kendala, 'date' => $note->tanggal_catatan]);
                }
            });
        CatatanPerkembangan::query()
            ->where('id_siswa', $siswa->getKey())
            ->where('status_pengajuan', 'Terverifikasi')
            ->latest('tanggal_catatan')
            ->limit(10)
            ->get()
            ->each(function ($note) use ($recommendations) {
                foreach ([['Tindakan Guru', $note->tindakan], ['Hasil Tindakan', $note->hasil_tindakan], ['Catatan Tambahan', $note->catatan_tambahan]] as [$title, $body]) {
                    if (filled($body)) {
                        $recommendations->push(compact('title', 'body') + ['date' => $note->tanggal_catatan]);
                    }
                }
            });

        return view('dev.tim-inklusi.students.recommendation', array_merge($this->viewContext($pendamping), [
            'siswa' => $siswa,
            'recommendations' => $recommendations->sortByDesc('date')->take(8)->values(),
        ]));
    }

    public function createNote(Request $request, Siswa $siswa): View
    {
        $pendamping = $this->pendamping($request);
        $this->ensureAssigned($pendamping, $siswa);

        return view('dev.tim-inklusi.notes.form', array_merge($this->viewContext($pendamping), [
            'siswa' => $siswa,
            'note' => new CatatanPendampingan(['tanggal_catatan' => now()->toDateString()]),
            'idempotencyKey' => (string) Str::uuid(),
        ]));
    }

    public function storeNote(Request $request): RedirectResponse
    {
        $pendamping = $this->pendamping($request);
        $data = $this->validateNote($request);
        $siswa = Siswa::query()->findOrFail($data['id_siswa']);
        $this->ensureAssigned($pendamping, $siswa);
        $submit = $data['aksi'] === 'kirim';

        $note = DB::transaction(function () use ($data, $pendamping, $siswa, $submit) {
            $existing = CatatanPendampingan::query()
                ->where('idempotency_key', $data['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                abort_unless((int) $existing->id_pembuat === (int) $pendamping->getKey(), 403);

                return $existing;
            }

            $note = new CatatanPendampingan;
            $note->forceFill($this->notePayload($data) + [
                'id_siswa' => $siswa->getKey(),
                'id_pembuat' => $pendamping->getKey(),
                'status_pengajuan' => $submit ? 'Dikirim' : 'Draft',
                'versi_pengajuan' => 1,
                'tanggal_kirim' => $submit ? now() : null,
                'idempotency_key' => $data['idempotency_key'],
            ])->save();

            return $note;
        });

        return redirect()->route('dev.tim-inklusi.notes.show', $note)
            ->with('success', $submit ? 'Catatan berhasil dikirim ke GPK.' : 'Draft catatan berhasil disimpan.');
    }

    public function editNote(Request $request, CatatanPendampingan $catatan): View
    {
        $pendamping = $this->pendamping($request);
        $this->ensureEditable($pendamping, $catatan);

        return view('dev.tim-inklusi.notes.form', array_merge($this->viewContext($pendamping), [
            'siswa' => $catatan->siswa,
            'note' => $catatan,
            'idempotencyKey' => $catatan->idempotency_key,
            'latestVerification' => $catatan->verifikasi()->latest('versi_pengajuan')->first(),
        ]));
    }

    public function updateNote(Request $request, CatatanPendampingan $catatan): RedirectResponse
    {
        $pendamping = $this->pendamping($request);
        $this->ensureEditable($pendamping, $catatan);
        $data = $this->validateNote($request, false);
        abort_unless((int) $data['id_siswa'] === (int) $catatan->id_siswa, 422);
        $submit = $data['aksi'] === 'kirim';

        DB::transaction(function () use ($catatan, $data, $submit) {
            $locked = CatatanPendampingan::query()->lockForUpdate()->findOrFail($catatan->getKey());
            abort_unless(in_array($locked->status_pengajuan, ['Draft', 'Perlu Revisi'], true), 403);
            $wasRevision = $locked->status_pengajuan === 'Perlu Revisi';

            $locked->forceFill($this->notePayload($data) + [
                'status_pengajuan' => $submit ? 'Dikirim' : $locked->status_pengajuan,
                'versi_pengajuan' => $submit && $wasRevision ? $locked->versi_pengajuan + 1 : $locked->versi_pengajuan,
                'tanggal_kirim' => $submit ? now() : $locked->tanggal_kirim,
            ])->save();
        });

        return redirect()->route('dev.tim-inklusi.notes.show', $catatan)
            ->with('success', $submit ? 'Catatan berhasil dikirim untuk verifikasi.' : 'Perubahan catatan berhasil disimpan.');
    }

    public function notes(Request $request): View
    {
        $pendamping = $this->pendamping($request);
        $search = trim((string) $request->query('cari'));
        $status = (string) $request->query('status');
        $allowed = ['Draft', 'Dikirim', 'Terverifikasi', 'Perlu Revisi'];
        $status = in_array($status, $allowed, true) ? $status : '';

        $notes = CatatanPendampingan::query()
            ->with('siswa.kelas')
            ->where('id_pembuat', $pendamping->getKey())
            ->when($search !== '', fn ($query) => $query->whereHas('siswa', fn ($students) => $students->where('nama_siswa', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('status_pengajuan', $status))
            ->latest('tanggal_catatan')
            ->latest('id_catatan_pendampingan')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('dev.tim-inklusi.notes.index', array_merge($this->viewContext($pendamping), [
            'notes' => $notes,
            'filters' => ['cari' => $search, 'status' => $status],
        ]));
    }

    public function showNote(Request $request, CatatanPendampingan $catatan): View
    {
        $pendamping = $this->pendamping($request);
        $this->ensureOwnedAndAssigned($pendamping, $catatan);
        $catatan->load(['siswa.kelas', 'verifikasi.verifikator']);

        return view('dev.tim-inklusi.notes.show', array_merge($this->viewContext($pendamping), [
            'note' => $catatan,
            'latestVerification' => $catatan->verifikasi->sortByDesc('versi_pengajuan')->first(),
        ]));
    }

    public function showDevelopmentNote(Request $request, Siswa $siswa, CatatanPerkembangan $catatan): View
    {
        $pendamping = $this->pendamping($request);
        $this->ensureAssigned($pendamping, $siswa);
        abort_unless((int) $catatan->id_siswa === (int) $siswa->getKey() && $catatan->status_pengajuan !== 'Draft', 404);
        $catatan->load(['siswa.kelas', 'pembuat', 'mataPelajaran', 'verifikasi.verifikator']);

        return view('dev.tim-inklusi.notes.development-show', array_merge($this->viewContext($pendamping), [
            'note' => $catatan,
            'latestVerification' => $catatan->verifikasi->sortByDesc('versi_pengajuan')->first(),
        ]));
    }

    private function validateNote(Request $request, bool $withToken = true): array
    {
        $rules = [
            'id_siswa' => ['required', 'integer', 'exists:siswa,id_siswa'],
            'tanggal_catatan' => ['required', 'date', 'before_or_equal:today'],
            'kegiatan_pendampingan' => ['required', 'string', 'max:5000'],
            'kendala' => ['nullable', 'string', 'max:5000'],
            'tindak_lanjut' => ['nullable', 'string', 'max:5000'],
            'aksi' => ['required', Rule::in(['simpan', 'kirim'])],
        ];
        if ($withToken) {
            $rules['idempotency_key'] = ['required', 'uuid'];
        }

        return $request->validate($rules);
    }

    private function notePayload(array $data): array
    {
        return [
            'tanggal_catatan' => $data['tanggal_catatan'],
            'kegiatan_pendampingan' => trim($data['kegiatan_pendampingan']),
            'kendala' => filled($data['kendala'] ?? null) ? trim($data['kendala']) : null,
            'tindak_lanjut' => filled($data['tindak_lanjut'] ?? null) ? trim($data['tindak_lanjut']) : null,
        ];
    }

    private function studentHistory(Request $request, Pengguna $pendamping, Siswa $siswa): LengthAwarePaginator
    {
        $development = CatatanPerkembangan::query()
            ->with(['pembuat', 'mataPelajaran'])
            ->where('id_siswa', $siswa->getKey())
            ->where('status_pengajuan', '!=', 'Draft')
            ->get()
            ->map(fn ($note) => ['type' => 'perkembangan', 'note' => $note, 'date' => $note->tanggal_catatan]);
        $assistance = CatatanPendampingan::query()
            ->with('pembuat')
            ->where('id_siswa', $siswa->getKey())
            ->where('id_pembuat', $pendamping->getKey())
            ->get()
            ->map(fn ($note) => ['type' => 'pendampingan', 'note' => $note, 'date' => $note->tanggal_catatan]);
        $all = $development->concat($assistance)->sortByDesc('date')->values();
        $perPage = $this->perPage($request);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function assignedStudents(Pengguna $pendamping): Builder
    {
        return Siswa::query()
            ->where('status_siswa', 'Aktif')
            ->whereHas('relasiPendampingSiswa', fn ($query) => $query->where('id_pengguna', $pendamping->getKey()));
    }

    private function ensureAssigned(Pengguna $pendamping, Siswa $siswa): void
    {
        abort_unless($this->assignedStudents($pendamping)->whereKey($siswa->getKey())->exists(), 404);
    }

    private function ensureOwnedAndAssigned(Pengguna $pendamping, CatatanPendampingan $note): void
    {
        abort_unless((int) $note->id_pembuat === (int) $pendamping->getKey(), 403);
        $this->ensureAssigned($pendamping, $note->siswa);
    }

    private function ensureEditable(Pengguna $pendamping, CatatanPendampingan $note): void
    {
        $this->ensureOwnedAndAssigned($pendamping, $note);
        abort_unless(in_array($note->status_pengajuan, ['Draft', 'Perlu Revisi'], true), 403);
    }

    private function pendamping(Request $request): Pengguna
    {
        return $request->attributes->get('tim_inklusi');
    }

    private function perPage(Request $request): int
    {
        $perPage = $request->integer('per_halaman');

        return in_array($perPage, [10, 30, 50, 100], true) ? $perPage : 10;
    }

    private function viewContext(Pengguna $pendamping): array
    {
        $initials = collect(preg_split('/\s+/', trim($pendamping->nama)))
            ->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->join('');

        return [
            'role' => 'tim-inklusi',
            'profile' => ['label' => 'Tim Inklusi', 'name' => $pendamping->nama, 'initials' => $initials ?: 'TI'],
            'previewRoles' => [
                'admin' => ['label' => 'Admin / Pengelola'],
                'guru-mapel' => ['label' => 'Guru Mata Pelajaran'],
                'tim-inklusi' => ['label' => 'Tim Inklusi'],
                'gpk' => ['label' => 'GPK'],
                'kepala-sekolah' => ['label' => 'Kepala Sekolah'],
                'orang-tua' => ['label' => 'Orang Tua'],
            ],
            'pendamping' => $pendamping,
        ];
    }
}
