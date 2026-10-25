<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Services\GuruMapelAccess;
use App\Services\GuruMapelNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GuruMapelController extends Controller
{
    public function __construct(private GuruMapelAccess $access) {}

    private function guru()
    {
        return Auth::guard('guru_dev')->user();
    }

    private function page(string $view, array $data = [])
    {
        $guru = $this->guru();
        $profile = ['label' => 'Guru Mata Pelajaran', 'name' => $guru->nama, 'initials' => mb_strtoupper(mb_substr($guru->nama, 0, 2))];

        return view('dev.guru-mapel.'.$view, $data + ['role' => 'guru-mapel', 'roles' => ['guru-mapel' => $profile], 'profile' => $profile, 'labels' => GuruMapelNoteService::LABELS]);
    }

    private function filters(Request $r): array
    {
        return $r->validate(['q' => 'nullable|string|max:100', 'tingkat' => 'nullable|in:Semua,X,XI,XII', 'status' => ['nullable', Rule::in(array_merge(['Semua'], array_keys(GuruMapelNoteService::LABELS)))], 'sumber' => 'nullable|in:Semua,perkembangan,pendampingan', 'siswa' => 'nullable|integer', 'per_page' => 'nullable|integer|in:10,30,50,100']);
    }

    public function dashboard()
    {
        $notes = $this->access->notes($this->guru());
        $stats = ['Siswa yang diajar' => $this->access->students($this->guru())->count(), 'Menunggu Verifikasi' => (clone $notes)->where('status_pengajuan', 'Dikirim')->count(), 'Terverifikasi' => (clone $notes)->where('status_pengajuan', 'Terverifikasi')->count()];

        return $this->page('dashboard', ['stats' => $stats, 'notes' => $notes->with(['siswa.kelas', 'mataPelajaran'])->orderByDesc('tanggal_catatan')->orderByDesc('id_catatan_perkembangan')->limit(5)->get()]);
    }

    public function students(Request $r)
    {
        $f = $this->filters($r);
        $q = $this->access->students($this->guru())->with(['kelas', 'relasiPendampingSiswa' => fn ($q) => $q->whereHas('pendamping', fn ($p) => $p->where('nama', 'like', 'DATA DEMO %')->where('email', 'like', '%@example.test')), 'relasiPendampingSiswa.pendamping.role'])
            ->withMax(['catatanPerkembangan as terakhir' => fn ($q) => $q->where('id_pembuat', $this->guru()->getKey())
                ->whereHas('mataPelajaran', fn ($m) => $m->where('nama_mapel', 'like', 'DATA DEMO %'))], 'tanggal_catatan');
        if ($f['q'] ?? '') {
            $q->where('nama_siswa', 'like', '%'.$f['q'].'%');
        }
        if (($f['tingkat'] ?? 'Semua') !== 'Semua') {
            $q->whereHas('kelas', fn ($q) => $q->where('tingkat', $f['tingkat']));
        }

        return $this->page('students.index', ['students' => $q->orderBy('nama_siswa')->paginate($f['per_page'] ?? 10)->withQueryString()]);
    }

    public function student(int $siswa)
    {
        $student = $this->access->student($this->guru(), $siswa);
        $history = collect();
        foreach (['perkembangan', 'pendampingan'] as $source) {
            $history = $history->concat($this->historyQuery($source)->where('id_siswa', $siswa)
                ->orderByDesc('tanggal_catatan')->limit(5)->get()
                ->map(fn ($note) => ['note' => $note, 'source' => $source]));
        }

        return $this->page('students.show', ['student' => $student, 'history' => $history->sortByDesc(fn ($item) => $item['note']->tanggal_catatan)->take(5)]);
    }

    public function recommendations(int $siswa)
    {
        return $this->page('recommendations.show', ['student' => $this->access->student($this->guru(), $siswa)]);
    }

    public function create(int $siswa)
    {
        return $this->form($siswa);
    }

    private function form(int $siswa, ?CatatanPerkembangan $note = null)
    {
        if (! $note && ! session()->has('guru_note_creation_token')) {
            session()->put('guru_note_creation_token', bin2hex(random_bytes(24)));
        }
        $student = $this->access->student($this->guru(), $siswa);
        $subjects = $this->access->assignments($this->guru())->where('id_kelas', $student->id_kelas)
            ->whereHas('kelas', fn ($q) => $q->when(Schema::hasColumn('kelas', 'status_kelas'), fn ($q) => $q->where('status_kelas', 'Aktif')))
            ->whereHas('mataPelajaran', fn ($q) => $q->when(Schema::hasColumn('mata_pelajaran', 'status_mapel'), fn ($q) => $q->where('status_mapel', 'Aktif')))->with('mataPelajaran')->get();

        return $this->page('notes.form', compact('student', 'subjects', 'note'));
    }

    public function store(Request $r, int $siswa, GuruMapelNoteService $service)
    {
        $this->access->student($this->guru(), $siswa);
        abort_unless(is_string($r->input('creation_token')) && is_string($r->session()->get('guru_note_creation_token'))
            && hash_equals($r->session()->get('guru_note_creation_token'), $r->input('creation_token')), 409, 'Form sudah digunakan. Buka form catatan baru.');
        $note = $service->save($this->guru(), $siswa, $r->all());
        $r->session()->forget('guru_note_creation_token');

        return redirect()->route('dev.guru-mapel.notes.show', $note->getKey())->with('success', $note->status_pengajuan === 'Draft' ? 'Draft Tersimpan' : 'Catatan dikirim ke GPK.');
    }

    public function status(Request $r)
    {
        $f = $this->filters($r);
        $q = $this->access->notes($this->guru())->with(['siswa.kelas', 'mataPelajaran']);
        if ($f['q'] ?? '') {
            $q->whereHas('siswa', fn ($q) => $q->where('nama_siswa', 'like', '%'.$f['q'].'%'));
        }
        if (($f['status'] ?? 'Semua') !== 'Semua') {
            $q->where('status_pengajuan', $f['status']);
        }

        return $this->page('notes.status', ['notes' => $q->orderByDesc('tanggal_catatan')->orderByDesc('id_catatan_perkembangan')->paginate($f['per_page'] ?? 10)->withQueryString()]);
    }

    private function note(int $id)
    {
        return $this->access->notes($this->guru())->with(['siswa.kelas', 'mataPelajaran', 'pembuat', 'verifikasi.verifikator'])->findOrFail($id);
    }

    public function show(int $id)
    {
        return $this->page('notes.show', ['note' => $this->note($id)]);
    }

    public function edit(int $id)
    {
        $note = $this->note($id);
        abort_unless(in_array($note->status_pengajuan, ['Draft', 'Perlu Revisi'], true), 409);

        return $this->form($note->id_siswa, $note);
    }

    public function update(Request $r, int $id, GuruMapelNoteService $service)
    {
        $note = $this->note($id);
        $service->save($this->guru(), $note->id_siswa, $r->all(), $id);

        return redirect()->route('dev.guru-mapel.notes.show', $id)->with('success', $r->input('aksi') === 'kirim' ? 'Catatan dikirim ke GPK.' : 'Draft Tersimpan');
    }

    private function historyQuery(string $source)
    {
        $q = $this->access->syntheticNotes($source === 'perkembangan' ? CatatanPerkembangan::query() : CatatanPendampingan::query())
            ->dipublikasikan()->whereIn('id_siswa', $this->access->students($this->guru())->select('id_siswa'));
        if ($source === 'perkembangan') {
            $q->whereHas('mataPelajaran', fn ($m) => $m->where('nama_mapel', 'like', 'DATA DEMO %'));
            // Cross-teacher access remains restricted to the actor's assigned subject/class pairs.
            $q->whereHas('siswa', fn ($s) => $s->whereExists(fn ($a) => $a->selectRaw('1')->from('relasi_guru_mapel_kelas as a')
                ->where('a.id_pengguna', $this->guru()->getKey())->whereColumn('a.id_kelas', 'siswa.id_kelas')->whereColumn('a.id_mapel', 'catatan_perkembangan.id_mapel')));
        }

        return $q->with(['siswa.kelas', 'pembuat']);
    }

    public function history(Request $r)
    {
        $f = $this->filters($r);
        if (isset($f['siswa'])) {
            $this->access->student($this->guru(), $f['siswa']);
        }
        // Union IDs and dates in SQL: pagination never loads all history bodies into memory.
        $queries = [];
        foreach (['perkembangan', 'pendampingan'] as $source) {
            if (($f['sumber'] ?? 'Semua') !== 'Semua' && $f['sumber'] !== $source) {
                continue;
            }
            $q = $this->historyQuery($source);
            if ($f['q'] ?? '') {
                $q->whereHas('siswa', fn ($s) => $s->where('nama_siswa', 'like', '%'.$f['q'].'%'));
            }
            if (isset($f['siswa'])) {
                $q->where('id_siswa', $f['siswa']);
            }
            $queries[] = $q->selectRaw("id_catatan_{$source} as id, tanggal_catatan, '{$source}' as sumber")->toBase();
        }
        $union = array_shift($queries);
        foreach ($queries as $q) {
            $union->unionAll($q);
        }
        $page = DB::query()->fromSub($union, 'history')->orderByDesc('tanggal_catatan')->orderBy('sumber')->orderByDesc('id')->paginate($f['per_page'] ?? 10)->withQueryString();
        $records = [];
        foreach ($page->getCollection()->groupBy('sumber') as $source => $rows) {
            $model = $source === 'perkembangan' ? new CatatanPerkembangan : new CatatanPendampingan;
            $records[$source] = $this->historyQuery($source)->whereIn($model->getKeyName(), $rows->pluck('id'))->get()->keyBy($model->getKeyName());
        }
        $page->setCollection($page->getCollection()->map(function ($row) use ($records) {
            return ['note' => $records[$row->sumber][$row->id], 'source' => $row->sumber];
        }));

        return $this->page('history.index', ['history' => $page]);
    }

    public function historyShow(string $source, int $id)
    {
        abort_unless(in_array($source, ['perkembangan', 'pendampingan'], true), 404);

        return $this->page('history.show', ['note' => $this->historyQuery($source)->with('verifikasi.verifikator')->findOrFail($id), 'source' => $source]);
    }
}
