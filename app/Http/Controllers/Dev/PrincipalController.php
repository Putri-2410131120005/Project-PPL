<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Models\TahunAjaran;
use App\Services\PrincipalExport;
use App\Services\PrincipalReports;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PrincipalController extends Controller
{
    // Controller Kepala Sekolah menyediakan tampilan baca, pratinjau, dan ekspor laporan.
    public function __construct(private PrincipalReports $data) {}

    private function principal(): ?Pengguna
    {
        $principal = Auth::guard('staff')->user();
        if ($principal?->role?->nama_role === 'Kepala Sekolah') {
            return $principal;
        }

        return null;
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'cari' => 'nullable|string|max:100', 'tingkat' => 'nullable|in:Semua,X,XI,XII',
            'periode' => 'nullable|string|max:30', 'tahun_ajaran' => 'nullable|string|max:20', 'siswa' => 'nullable|integer|min:1',
            'semester' => 'nullable|in:ganjil,genap',
            'bulan' => 'nullable|integer|between:1,12', 'tahun' => 'nullable|integer|between:2000,2100', 'per_page' => 'nullable|integer|in:10,30,50,100',
            'per_halaman' => 'nullable|integer|in:10,30,50,100']);
        $filters['per_page'] = $filters['per_halaman'] ?? $filters['per_page'] ?? 10;
        $filters['q'] = $filters['q'] ?? $filters['cari'] ?? null;
        if (! empty($filters['siswa'])) {
            $this->data->students()->findOrFail($filters['siswa']);
        }

        return $filters;
    }

    private function page(string $view, array $data = [])
    {
        $principal = $this->principal();

        return view('dev.principal.'.$view, $data + ['profile' => [
            'name' => $principal?->nama ?? 'Kepala Sekolah',
            'label' => 'Kepala Sekolah',
            'initials' => Str::of($principal?->nama ?? 'Kepala Sekolah')->explode(' ')->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode(''),
        ]]);
    }

    private function options(): array
    {
        return ['periods' => $this->data->recaps()->distinct()->orderBy('periode')->pluck('periode'),
            'schoolYears' => $this->data->students()->with('kelas')->get()->pluck('kelas.tahun_ajaran')->unique()->sort()->values()];
    }

    public function dashboard()
    {
        // Menampilkan ringkasan laporan dan status publikasi untuk Kepala Sekolah.
        $latest = $this->data->reports()->orderByDesc('tanggal_laporan')->first();
        $stats = ['students' => $this->data->students()->count(),
            'verified' => $this->data->notes('perkembangan', false)->count() + $this->data->notes('pendampingan', false)->count(),
            'period' => $latest?->periode];
        $archive = $latest ? $this->archiveQuery(['periode' => $latest->periode])->first() : null;

        return $this->page('dashboard', compact('stats', 'archive'));
    }

    public function students(Request $r)
    {
        // Menampilkan daftar siswa dengan pencarian dan filter kelas.
        $f = $this->filters($r);
        $students = $this->data->studentFilters($this->data->students(), $f)->with(['kelas', 'relasiPendampingSiswa.pendamping.role'])
            ->withMax(['catatanPerkembangan as last_note' => fn ($q) => $q->whereIn('id_catatan_perkembangan', $this->data->notes('perkembangan')->select('id_catatan_perkembangan'))], 'tanggal_catatan')
            ->orderBy('nama_siswa')->paginate($f['per_page'] ?? 10)->withQueryString();

        return $this->page('students', compact('students'));
    }

    public function student(int $siswa)
    {
        // Menampilkan ringkasan perkembangan satu siswa.
        $student = $this->data->students()->with(['kelas', 'relasiPendampingSiswa.pendamping.role'])->findOrFail($siswa);
        $recaps = $this->data->recaps(['siswa' => $siswa])->orderByDesc('tanggal_rekap')->get();

        return $this->page('student', compact('student', 'recaps'));
    }

    public function recaps(Request $r)
    {
        // Menampilkan rekap perkembangan yang telah tersedia.
        $f = $this->filters($r);
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->recapPeriod($r, $schoolYears);
        $f['tahun_ajaran'] = $schoolYear;
        $verifiedStudents = $this->data->studentsForVerifiedRecap($f, $periodStart, $periodEnd)
            ->orderBy('nama_siswa')->paginate($f['per_page'] ?? 10, ['*'], 'catatan_page')->withQueryString();
        $filters = ['cari' => $f['q'] ?? '', 'tingkat' => $f['tingkat'] ?? 'Semua',
            'semester' => $semester, 'tahun_ajaran' => $schoolYear];

        return $this->page('recaps', compact('verifiedStudents', 'schoolYears', 'filters', 'period'));
    }

    public function verifiedRecap(Request $r, int $siswa)
    {
        // Menampilkan rekap terverifikasi untuk siswa yang dipilih.
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->recapPeriod($r, $schoolYears);
        $student = $this->data->studentsForVerifiedRecap(['tahun_ajaran' => $schoolYear], $periodStart, $periodEnd)->findOrFail($siswa);
        $sources = $this->data->verifiedSources($student, $periodStart, $periodEnd);

        return $this->page('verified-recap', compact('student', 'sources', 'semester', 'schoolYear', 'period'));
    }

    public function downloadVerifiedRecapsZip(Request $r, PrincipalExport $export)
    {
        // Mengunduh kumpulan rekap terverifikasi dalam arsip ZIP.
        $f = $this->filters($r);
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->recapPeriod($r, $schoolYears);
        $f['tahun_ajaran'] = $schoolYear;
        $students = $this->data->studentsForVerifiedRecap($f, $periodStart, $periodEnd)
            ->orderBy('nama_siswa')->limit(101)->get();
        abort_if($students->count() > 100, 422, 'Pilih maksimal 100 siswa untuk satu unduhan.');
        abort_if($students->isEmpty(), 404, 'Belum ada catatan terverifikasi untuk pilihan ini.');
        $documents = $students->map(fn ($student) => $this->verifiedDocument(
            $student,
            $this->data->verifiedSources($student, $periodStart, $periodEnd),
            $period,
        ))->all();
        $classSuffix = ! empty($f['tingkat']) && $f['tingkat'] !== 'Semua' ? '-kelas-'.Str::lower($f['tingkat']) : '';
        $filename = 'rekap-semua-siswa'.$classSuffix.'-'.Str::slug(str_replace('/', '-', $period)).'.zip';

        return response($export->zip($documents), 200, ['Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"', 'Cache-Control' => 'private, no-store']);
    }

    public function previewVerifiedRecap(Request $r, int $siswa)
    {
        // Menampilkan pratinjau PDF rekap terverifikasi.
        [$document, $student, $semester, $schoolYear] = $this->verifiedRecapDocument($r, $siswa);
        $documents = [$document];
        $pdfUrl = route('dev.principal.verified-recap.download', ['siswa' => $student->getKey(),
            'semester' => $semester, 'tahun_ajaran' => $schoolYear, 'inline' => 1]);
        $backUrl = route('dev.principal.verified-recap', ['siswa' => $student->getKey(),
            'semester' => $semester, 'tahun_ajaran' => $schoolYear]);

        return $this->page('preview', compact('documents', 'pdfUrl', 'backUrl'));
    }

    public function downloadVerifiedRecap(Request $r, int $siswa, PrincipalExport $export)
    {
        // Menghasilkan dan mengunduh PDF rekap terverifikasi.
        [$document] = $this->verifiedRecapDocument($r, $siswa);
        $filename = 'rekap-perkembangan-'.Str::slug($document['student']).'-'.Str::slug(str_replace('/', '-', $document['period'])).'.pdf';

        return response($export->pdf([$document]), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => ($r->query('inline') === '1' ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function verifiedRecapDocument(Request $r, int $siswa): array
    {
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->recapPeriod($r, $schoolYears);
        $student = $this->data->studentsForVerifiedRecap(['tahun_ajaran' => $schoolYear], $periodStart, $periodEnd)->findOrFail($siswa);
        $document = $this->verifiedDocument($student, $this->data->verifiedSources($student, $periodStart, $periodEnd), $period);

        return [$document, $student, $semester, $schoolYear];
    }

    private function verifiedDocument($student, Collection $sources, string $period): array
    {
        $principal = $this->principal();
        $gpk = Pengguna::query()->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($role) => $role->where('nama_role', 'GPK'))->orderBy('id_pengguna')->first();

        return ['id' => null, 'student' => $student->nama_siswa, 'nis' => $student->nis,
            'class' => $student->kelas?->nama_kelas ?: '—', 'period' => $period,
            'summary' => 'Rekap catatan perkembangan dan pendampingan yang telah diverifikasi GPK.', 'report' => null,
            'author' => $gpk?->nama ?? 'Guru Pembimbing Khusus (GPK)', 'author_nip' => $gpk?->nip,
            'published' => null, 'sources' => $sources->map(function (array $source) {
                $source['date'] = $source['date']->format('Y-m-d');

                return $source;
            })->all(), 'headmaster' => $principal?->nama, 'headmaster_nip' => $principal?->nip,
            'printed_date' => now('Asia/Makassar')->locale('id')->translatedFormat('d F Y')];
    }

    private function schoolYears(): Collection
    {
        $years = TahunAjaran::query()->orderByRaw("status = 'Aktif' desc")->orderByDesc('mulai_ganjil')->pluck('nama');

        return $years->isNotEmpty() ? $years : $this->data->students()->with('kelas')->get()
            ->pluck('kelas.tahun_ajaran')->filter()->unique()->sortDesc()->values();
    }

    private function recapPeriod(Request $r, Collection $schoolYears): array
    {
        $now = CarbonImmutable::now('Asia/Makassar');
        $defaultYear = TahunAjaran::aktif()?->nama ?: ($now->month >= 7 ? $now->year.'/'.($now->year + 1) : ($now->year - 1).'/'.$now->year);
        $schoolYear = trim((string) $r->query('tahun_ajaran', ''));
        if (! preg_match('/^\d{4}\/\d{4}$/', $schoolYear)) {
            $schoolYear = $schoolYears->contains($defaultYear) ? $defaultYear : (string) ($schoolYears->first() ?: $defaultYear);
        }
        $semester = strtolower((string) $r->query('semester', ''));
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = $now->month >= 7 ? 'ganjil' : 'genap';
        }
        $master = TahunAjaran::query()->where('nama', $schoolYear)->first();
        if ($master) {
            $start = CarbonImmutable::parse($semester === 'ganjil' ? $master->mulai_ganjil : $master->mulai_genap)->startOfDay();
            $end = CarbonImmutable::parse($semester === 'ganjil' ? $master->selesai_ganjil : $master->selesai_genap)->endOfDay();
        } else {
            [$startYear, $endYear] = array_map('intval', explode('/', $schoolYear));
            abort_unless($endYear === $startYear + 1, 422, 'Tahun ajaran tidak valid.');
            $start = CarbonImmutable::create($semester === 'ganjil' ? $startYear : $endYear, $semester === 'ganjil' ? 7 : 1, 1)->startOfDay();
            $end = $semester === 'ganjil' ? CarbonImmutable::create($startYear, 12, 31)->endOfDay() : CarbonImmutable::create($endYear, 6, 30)->endOfDay();
        }

        return [$semester, $schoolYear, 'Semester '.ucfirst($semester).' '.$schoolYear, $start, $end];
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
        // Menampilkan arsip laporan berdasarkan periode.
        $f = $this->filters($r);
        $archives = $this->archiveQuery($f)->orderByDesc('updated_at')->paginate($f['per_page'] ?? 10)->withQueryString();
        $years = $this->data->reports()->get(['tanggal_laporan'])->map(fn ($l) => $l->tanggal_laporan->year)->unique()->sortDesc()->values();

        return $this->page('archives', compact('archives', 'years'));
    }

    public function period(Request $r)
    {
        // Menampilkan laporan bulanan untuk periode yang dipilih.
        $f = $this->filters($r);
        abort_unless(! empty($f['periode']) && $this->data->reports(['periode' => $f['periode']])->exists(), 404);
        $reports = $this->data->reports($f)->with(['rekap.siswa.kelas', 'rekap.sumberCatatan'])
            ->orderBy('id_laporan')->paginate($f['per_page'] ?? 10)->withQueryString();
        $studentIds = $this->data->reports(['periode' => $f['periode']])
            ->join('rekap_perkembangan as period_recap', 'period_recap.id_rekap', '=', 'laporan_perkembangan.id_rekap')
            ->select('period_recap.id_siswa');
        $students = $this->data->students()->whereIn('id_siswa', $studentIds)->orderBy('nama_siswa')->get();
        $selectedStudent = null;
        if (! empty($f['siswa'])) {
            $selectedReport = $reports->getCollection()->first();
            if ($selectedReport) {
                $recap = $this->data->detail($selectedReport->id_rekap);
                $selectedStudent = [
                    'id_siswa' => $selectedReport->rekap->siswa->getKey(),
                    'nama_siswa' => $selectedReport->rekap->siswa->nama_siswa,
                    'kelas' => $selectedReport->rekap->siswa->kelas->nama_kelas,
                    'jumlah_catatan' => $recap->sumberCatatan->count(),
                    'ringkasan' => collect(preg_split('/(?:\r\n|\r|\n|•)+/u', $selectedReport->isi_laporan))->map(fn ($line) => trim($line))->filter()->values()->all(),
                    'records' => $this->data->sources($recap)->map(fn ($source) => [
                        'date' => $source['date'],
                        'source' => $source['source'].($source['subject'] ? ' · '.$source['subject'] : ''),
                        'author' => $source['teacher'],
                        'summary' => $source['result'] ?: $source['event'],
                        'recommendation' => $source['action'],
                        'verification_recommendation' => $source['recommendation'],
                        'verified_by' => $source['verified_by'],
                    ])->all(),
                ];
            }
        }

        return $this->page('period', compact('reports', 'students', 'selectedStudent') + ['period' => $f['periode']]);
    }

    private function documents(Request $r, ?int $id = null, string $kind = 'recaps'): array
    {
        $f = $this->filters($r);
        if ($id) {
            $documents = [$this->data->document($this->data->detail($id))];
        } elseif ($kind === 'reports') {
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

        $principal = $this->principal();

        return collect($documents)->map(fn (array $document) => $document + [
            'headmaster' => $principal?->nama,
            'headmaster_nip' => $principal?->nip,
            'printed_date' => now('Asia/Makassar')->locale('id')->translatedFormat('d F Y'),
        ])->all();
    }

    public function preview(Request $r, ?int $rekap = null)
    {
        // Menampilkan pratinjau dokumen sebelum ekspor.
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
        // Mengekspor seluruh rekap sesuai format yang dipilih.
        return $this->export($r, $format, $export);
    }

    public function exportRecap(Request $r, int $rekap, string $format, PrincipalExport $export)
    {
        // Mengekspor satu rekap perkembangan.
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        return $this->export($r, $format, $export, $rekap);
    }

    public function exportPeriod(Request $r, string $format, PrincipalExport $export)
    {
        // Mengekspor laporan periode Kepala Sekolah.
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
}
