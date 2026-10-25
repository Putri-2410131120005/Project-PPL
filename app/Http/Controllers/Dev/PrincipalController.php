<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\PrincipalExport;
use App\Services\PrincipalReports;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrincipalController extends Controller
{
    public function __construct(private PrincipalReports $data) {}

    private function filters(Request $request): array
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'tingkat' => 'nullable|in:Semua,X,XI,XII',
            'periode' => 'nullable|string|max:30', 'tahun_ajaran' => 'nullable|string|max:20', 'siswa' => 'nullable|integer|min:1',
            'bulan' => 'nullable|integer|between:1,12', 'tahun' => 'nullable|integer|between:2000,2100', 'per_page' => 'nullable|integer|in:10,30,50,100']);
        if (! empty($filters['siswa'])) {
            $this->data->students()->findOrFail($filters['siswa']);
        }

        return $filters;
    }

    private function page(string $view, array $data = [])
    {
        return view('dev.principal.'.$view, $data + ['profile' => ['name' => 'DATA DEMO', 'label' => 'Kepala Sekolah', 'initials' => 'KS']]);
    }

    private function options(): array
    {
        return ['periods' => $this->data->recaps()->distinct()->orderBy('periode')->pluck('periode'),
            'schoolYears' => $this->data->students()->with('kelas')->get()->pluck('kelas.tahun_ajaran')->unique()->sort()->values()];
    }

    public function dashboard()
    {
        $latest = $this->data->reports()->orderByDesc('tanggal_laporan')->first();
        $stats = ['students' => $this->data->students()->count(),
            'verified' => $this->data->notes('perkembangan', false)->count() + $this->data->notes('pendampingan', false)->count(),
            'period' => $latest?->periode];
        $archive = $latest ? $this->archiveQuery(['periode' => $latest->periode])->first() : null;

        return $this->page('dashboard', compact('stats', 'archive'));
    }

    public function students(Request $r)
    {
        $f = $this->filters($r);
        $students = $this->data->studentFilters($this->data->students(), $f)->with(['kelas',
            'relasiPendampingSiswa' => fn ($q) => $q->whereHas('pendamping', fn ($p) => $p->where('nama', 'like', 'DATA DEMO %')->where('email', 'like', '%@example.test')),
            'relasiPendampingSiswa.pendamping.role'])
            ->withMax(['catatanPerkembangan as last_note' => fn ($q) => $q->whereIn('id_catatan_perkembangan', $this->data->notes('perkembangan')->select('id_catatan_perkembangan'))], 'tanggal_catatan')
            ->orderBy('nama_siswa')->paginate($f['per_page'] ?? 10)->withQueryString();

        return $this->page('students', compact('students'));
    }

    public function student(int $siswa)
    {
        $student = $this->data->students()->with(['kelas', 'relasiPendampingSiswa' => fn ($q) => $q->whereHas('pendamping', fn ($p) => $p->where('nama', 'like', 'DATA DEMO %')->where('email', 'like', '%@example.test')), 'relasiPendampingSiswa.pendamping.role'])->findOrFail($siswa);
        $recaps = $this->data->recaps(['siswa' => $siswa])->orderByDesc('tanggal_rekap')->get();

        return $this->page('student', compact('student', 'recaps'));
    }

    public function recaps(Request $r)
    {
        $f = $this->filters($r);
        $recaps = $this->data->recaps($f)->with('siswa.kelas')->withCount('sumberCatatan')
            ->orderByDesc('tanggal_rekap')->orderByDesc('id_rekap')->paginate($f['per_page'] ?? 10)->withQueryString();

        return $this->page('recaps', compact('recaps') + $this->options());
    }

    public function recap(int $rekap)
    {
        $recap = $this->data->detail($rekap);
        $sources = $this->data->sources($recap);

        return $this->page('recap', compact('recap', 'sources'));
    }

    public function semester(int $rekap)
    {
        $recap = $this->data->detail($rekap);

        return $this->page('semester', compact('recap'));
    }

    public function note(int $rekap, int $link)
    {
        $recap = $this->data->detail($rekap);
        $sources = $this->data->sources($recap);
        $selectedNote = $sources->firstWhere('link', $link);
        abort_unless($selectedNote, 404);

        return $this->page('recap', compact('recap', 'sources', 'selectedNote'));
    }

    private function archiveQuery(array $filters = [])
    {
        return $this->data->reports($filters)->join('rekap_perkembangan as archived_recap', 'archived_recap.id_rekap', '=', 'laporan_perkembangan.id_rekap')
            ->leftJoin('sumber_rekap_catatan as archive_source', 'archive_source.id_rekap', '=', 'laporan_perkembangan.id_rekap')
            ->selectRaw('laporan_perkembangan.periode, COUNT(DISTINCT archived_recap.id_siswa) as student_count, COUNT(DISTINCT laporan_perkembangan.id_laporan) as report_count, MAX(tanggal_laporan) as updated_at, COUNT(DISTINCT archive_source.id_sumber_rekap) as note_count')
            ->groupBy('laporan_perkembangan.periode');
    }

    public function archives(Request $r)
    {
        $f = $this->filters($r);
        $archives = $this->archiveQuery($f)->orderByDesc('updated_at')->paginate($f['per_page'] ?? 10)->withQueryString();
        $years = $this->data->reports()->get(['tanggal_laporan'])->map(fn ($l) => $l->tanggal_laporan->year)->unique()->sortDesc()->values();

        return $this->page('archives', compact('archives', 'years'));
    }

    public function period(Request $r)
    {
        $f = $this->filters($r);
        abort_unless(! empty($f['periode']) && $this->data->reports(['periode' => $f['periode']])->exists(), 404);
        $reports = $this->data->reports($f)->with(['rekap.siswa.kelas'])
            ->orderBy('id_laporan')->paginate($f['per_page'] ?? 10)->withQueryString();
        $studentIds = $this->data->reports(['periode' => $f['periode']])
            ->join('rekap_perkembangan as period_recap', 'period_recap.id_rekap', '=', 'laporan_perkembangan.id_rekap')
            ->select('period_recap.id_siswa');
        $students = $this->data->students()->whereIn('id_siswa', $studentIds)->orderBy('nama_siswa')->get();

        return $this->page('period', compact('reports', 'students') + ['period' => $f['periode']]);
    }

    private function documents(Request $r, ?int $id = null, string $kind = 'recaps'): array
    {
        $f = $this->filters($r);
        if ($id) {
            return [$this->data->document($this->data->detail($id))];
        }
        if ($kind === 'reports') {
            $reports = $this->data->reports($f)->orderBy('id_laporan')->limit(101)->get();
            abort_if($reports->count() > 100, 422, 'Pilih maksimal 100 laporan untuk satu unduhan.');
            $recaps = $this->data->loadDetails($this->data->recaps()->whereIn('id_rekap', $reports->pluck('id_rekap')))->get()->keyBy('id_rekap');
            $documents = $reports->map(fn ($report) => $this->data->document($recaps[$report->id_rekap], $report))->all();
        } else {
            $recaps = $this->data->loadDetails($this->data->recaps($f))->orderBy('id_rekap')->limit(101)->get();
            abort_if($recaps->count() > 100, 422, 'Pilih maksimal 100 rekap untuk satu unduhan.');
            $documents = $recaps->map(fn ($recap) => $this->data->document($recap))->all();
        }
        abort_unless(count($documents) > 0, 404, 'Belum ada laporan resmi yang tersedia untuk pilihan ini.');

        return $documents;
    }

    public function preview(Request $r, ?int $rekap = null)
    {
        if (! $rekap) {
            $r->validate(['periode' => 'required|string|max:30']);
        }
        $documents = $this->documents($r, $rekap, $rekap ? 'recaps' : 'reports');
        $pdfUrl = $rekap ? route('dev.principal.recap.export', ['rekap' => $rekap, 'format' => 'pdf'] + $r->query() + ['inline' => 1])
            : route('dev.principal.period.export', ['format' => 'pdf'] + $r->query() + ['inline' => 1]);

        $backUrl = $rekap ? route('dev.principal.recap', $rekap) : route('dev.principal.period', $r->except('inline'));

        return $this->page('preview', compact('documents', 'pdfUrl', 'backUrl'));
    }

    public function exportRecaps(Request $r, string $format, PrincipalExport $export)
    {
        return $this->export($r, $format, $export);
    }

    public function exportRecap(Request $r, int $rekap, string $format, PrincipalExport $export)
    {
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        return $this->export($r, $format, $export, $rekap);
    }

    public function exportPeriod(Request $r, string $format, PrincipalExport $export)
    {
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);
        $r->validate(['periode' => 'required|string|max:30']);

        return $this->export($r, $format, $export, null, 'reports');
    }

    private function export(Request $r, string $format, PrincipalExport $export, ?int $id = null, string $kind = 'recaps')
    {
        abort_unless(in_array($format, ['pdf', 'xlsx', 'zip'], true), 404);
        $documents = $this->documents($r, $id, $kind);
        $mime = ['pdf' => 'application/pdf', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'zip' => 'application/zip'][$format];
        $file = 'smart-abk-'.($id ? Str::slug($documents[0]['nis'].'-'.$documents[0]['period']) : Str::slug($r->query('periode', 'rekap')).'-'.now()->format('Ymd')).'.'.$format;
        $body = $export->$format($documents);

        return response($body, 200, ['Content-Type' => $mime, 'Content-Disposition' => ($format === 'pdf' && $r->query('inline') === '1' ? 'inline' : 'attachment').'; filename="'.$file.'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function logout()
    {
        return $this->page('logout');
    }
}
