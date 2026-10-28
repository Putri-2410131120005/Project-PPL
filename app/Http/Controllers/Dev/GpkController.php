<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\LaporanBulanan;
use App\Models\Pengguna;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\VerifikasiCatatan;
use App\Services\GpkVerifikasiService;
use App\Services\LaporanBulananService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class GpkController extends Controller
{
    public function __construct(
        private readonly GpkVerifikasiService $verifikasiService,
        private readonly LaporanBulananService $laporanBulananService,
    ) {}

    public function dashboard(Request $request): View
    {
        $gpk = $this->gpk($request);
        $notes = $this->notesFor();
        $pending = $notes->filter(fn ($item) => $item['is_pending']);

        return view('dev.gpk.dashboard', $this->context($gpk) + [
            'stats' => [
                'students' => $this->assignedStudents($gpk)->count(),
                'pending' => $pending->count(),
                'revision' => $notes->where('status', 'Perlu Revisi')->count(),
                'verified' => $notes->where('status', 'Terverifikasi')->count(),
            ],
            'pendingNotes' => $pending->sortByDesc('sort_date')->take(5)->values(),
        ]);
    }

    public function students(Request $request): View
    {
        $gpk = $this->gpk($request);
        $search = trim((string) $request->query('cari'));
        $level = $this->level($request);
        $students = $this->assignedStudents($gpk)
            ->with(['kelas', 'relasiPendampingSiswa' => fn ($query) => $query->with('pendamping.role')])
            ->withMax(['catatanPerkembangan as perkembangan_terakhir' => fn ($query) => $query->where('status_pengajuan', '!=', 'Draft')], 'tanggal_catatan')
            ->withMax(['catatanPendampingan as pendampingan_terakhir' => fn ($query) => $query->where('status_pengajuan', '!=', 'Draft')], 'tanggal_catatan')
            ->when($search !== '', fn ($query) => $query->where(fn ($student) => $student
                ->where('nama_siswa', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%")))
            ->when($level !== '', fn ($query) => $query->whereHas('kelas', fn ($classes) => $classes->where('tingkat', $level)))
            ->orderBy('nama_siswa')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('dev.gpk.students.index', $this->context($gpk) + [
            'students' => $students,
            'filters' => ['cari' => $search, 'tingkat' => $level],
        ]);
    }

    public function student(Request $request, Siswa $siswa): View
    {
        $gpk = $this->gpk($request);
        $this->ensureAssigned($gpk, $siswa);
        $siswa->load([
            'kelas',
            'relasiPendampingSiswa.pendamping.role',
            'relasiOrangTuaSiswa.orangTua.pengguna',
        ]);

        $history = $this->paginateCollection(
            $this->notesFor($siswa->getKey())
                ->filter(fn ($item) => $item['status'] === 'Terverifikasi')
                ->sortByDesc('sort_date')->values(),
            $request
        );

        return view('dev.gpk.students.show', $this->context($gpk) + compact('siswa', 'history'));
    }

    public function notes(Request $request): View
    {
        $gpk = $this->gpk($request);
        $search = trim((string) $request->query('cari'));
        $status = (string) $request->query('status');
        $source = (string) $request->query('sumber');
        $status = in_array($status, ['menunggu', 'Terverifikasi', 'Perlu Revisi'], true) ? $status : '';
        $source = in_array($source, ['perkembangan', 'pendampingan'], true) ? $source : '';

        $notes = $this->notesFor()
            ->when($search !== '', fn ($items) => $items->filter(fn ($item) => Str::contains(Str::lower($item['student']->nama_siswa), Str::lower($search))))
            ->when($status === 'menunggu', fn ($items) => $items->filter(fn ($item) => $item['is_pending']))
            ->when($status !== '' && $status !== 'menunggu', fn ($items) => $items->where('status', $status))
            ->when($source !== '', fn ($items) => $items->where('type', $source))
            ->sortByDesc('sort_date')->values();

        return view('dev.gpk.notes.index', $this->context($gpk) + [
            'notes' => $this->paginateCollection($notes, $request),
            'filters' => compact('search', 'status', 'source'),
        ]);
    }

    public function note(Request $request, string $type, int $id): View
    {
        $gpk = $this->gpk($request);
        $note = $this->findNote($type, $id);
        $this->ensureReviewable($type, $note);
        $note->load($type === 'perkembangan'
            ? ['siswa.kelas', 'pembuat.role', 'mataPelajaran', 'peninjauGpk', 'aktivitasGpk.gpk', 'verifikasi.verifikator', 'verifikasi.publikator']
            : ['siswa.kelas', 'pembuat.role', 'peninjauGpk', 'aktivitasGpk.gpk', 'verifikasi.verifikator', 'verifikasi.publikator']);

        $latestVerification = $note->verifikasi->first(
            fn ($verification) => (int) $verification->versi_pengajuan === (int) $note->versi_pengajuan
        );

        return view('dev.gpk.notes.show', $this->context($gpk) + compact('note', 'type', 'latestVerification'));
    }

    public function claimNote(Request $request, string $type, int $id): RedirectResponse
    {
        $gpk = $this->gpk($request);
        $this->ensureReviewable($type, $this->findNote($type, $id));
        try {
            $this->verifikasiService->mulaiMeninjau($type, $id, $gpk->getKey());
        } catch (DomainException $exception) {
            return back()->withErrors(['verifikasi' => $exception->getMessage()]);
        }

        return redirect()->route('dev.gpk.notes.show', ['type' => $type, 'id' => $id])
            ->with('success', 'Catatan berhasil diambil dan sekarang sedang Anda tinjau.');
    }

    public function verify(Request $request, string $type, int $id): RedirectResponse
    {
        $gpk = $this->gpk($request);
        $note = $this->findNote($type, $id);
        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['Terverifikasi', 'Perlu Revisi'])],
            'alasan' => [Rule::requiredIf($request->input('keputusan') === 'Perlu Revisi'), 'nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->verifikasiService->verifikasi($type, $id, $gpk->getKey(), $data['keputusan'], $data['alasan'] ?? null);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['verifikasi' => $exception->getMessage()]);
        }

        return redirect()->route('dev.gpk.notes.show', ['type' => $type, 'id' => $id])
            ->with('success', $data['keputusan'] === 'Terverifikasi'
                ? 'Catatan berhasil diverifikasi dan langsung tersedia bagi Orang Tua/Wali.'
                : 'Catatan dikembalikan kepada pembuat untuk direvisi.');
    }

    public function history(Request $request): View
    {
        $gpk = $this->gpk($request);
        $search = trim((string) $request->query('cari'));
        $source = (string) $request->query('sumber');
        $source = in_array($source, ['perkembangan', 'pendampingan'], true) ? $source : '';
        $history = VerifikasiCatatan::query()
            ->with([
                'verifikator', 'publikator',
                'catatanPerkembangan.siswa.kelas', 'catatanPerkembangan.pembuat', 'catatanPerkembangan.mataPelajaran',
                'catatanPendampingan.siswa.kelas', 'catatanPendampingan.pembuat',
            ])
            ->when($source === 'perkembangan', fn ($query) => $query->whereNotNull('id_catatan_perkembangan'))
            ->when($source === 'pendampingan', fn ($query) => $query->whereNotNull('id_catatan_pendampingan'))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->whereHas('catatanPerkembangan.siswa', fn ($students) => $students->where('nama_siswa', 'like', "%{$search}%"))
                ->orWhereHas('catatanPendampingan.siswa', fn ($students) => $students->where('nama_siswa', 'like', "%{$search}%"))))
            ->latest('tanggal_verifikasi')
            ->latest('id_verifikasi')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('dev.gpk.history.index', $this->context($gpk) + [
            'history' => $history,
            'filters' => compact('search', 'source'),
        ]);
    }

    public function recaps(Request $request): View
    {
        $gpk = $this->gpk($request);
        $search = trim((string) $request->query('cari'));
        $level = $this->level($request);
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->periodContext($request, $schoolYears);

        $students = Siswa::query()->where('status_siswa', 'Aktif')
            ->with('kelas')
            ->withCount([
                'catatanPerkembangan as jumlah_catatan_perkembangan' => fn (Builder $query) => $this->verifiedNotesInPeriod($query, 'perkembangan', $periodStart, $periodEnd),
                'catatanPendampingan as jumlah_catatan_pendampingan' => fn (Builder $query) => $this->verifiedNotesInPeriod($query, 'pendampingan', $periodStart, $periodEnd),
            ])
            ->when($search !== '', fn ($query) => $query->where('nama_siswa', 'like', "%{$search}%"))
            ->when($level !== '', fn ($query) => $query->whereHas('kelas', fn ($classes) => $classes->where('tingkat', $level)))
            ->when($schoolYear !== '', fn ($query) => $query->whereHas('kelas', fn ($classes) => $classes->where('tahun_ajaran', $schoolYear)))
            ->orderBy('nama_siswa')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('dev.gpk.recaps.index', $this->context($gpk) + [
            'students' => $students,
            'schoolYears' => $schoolYears,
            'period' => $period,
            'filters' => ['cari' => $search, 'tingkat' => $level, 'tahun_ajaran' => $schoolYear, 'semester' => $semester],
        ]);
    }

    public function recap(Request $request, Siswa $siswa): View
    {
        $gpk = $this->gpk($request);
        abort_unless($siswa->status_siswa === 'Aktif', 404);
        $siswa->load('kelas');
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->periodContext($request, $schoolYears);
        $records = $this->laporanBulananService->recordsForStudent($siswa, $periodStart, $periodEnd);

        return view('dev.gpk.recaps.show', $this->context($gpk) + compact(
            'siswa', 'records', 'semester', 'schoolYear', 'period'
        ));
    }

    public function downloadRecapsZip(Request $request): Response
    {
        $gpk = $this->gpk($request);
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->periodContext($request, $schoolYears);
        $level = $this->level($request);
        $students = Siswa::query()->where('status_siswa', 'Aktif')
            ->with('kelas')
            ->when($schoolYear !== '', fn ($query) => $query
                ->whereHas('kelas', fn ($classes) => $classes->where('tahun_ajaran', $schoolYear)))
            ->when($level !== '', fn ($query) => $query
                ->whereHas('kelas', fn ($classes) => $classes->where('tingkat', $level)))
            ->orderBy('nama_siswa')
            ->get();

        $temporaryPath = tempnam(sys_get_temp_dir(), 'smart-abk-rekap-');
        abort_if($temporaryPath === false, 500, 'Berkas ZIP sementara tidak dapat dibuat.');

        $zip = new ZipArchive;
        $openResult = $zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            @unlink($temporaryPath);
            abort(500, 'Berkas ZIP rekap tidak dapat dibuat.');
        }

        try {
            $signature = $this->signatureData($gpk);

            foreach ($students as $index => $student) {
                $document = [
                    'siswa' => $student,
                    'records' => $this->laporanBulananService->recordsForStudent($student, $periodStart, $periodEnd),
                    'semester' => $semester,
                    'schoolYear' => $schoolYear,
                    'period' => $period,
                    ...$signature,
                ];
                $studentIdentifier = Str::slug((string) ($student->nis ?: $student->getKey()));
                $entryName = sprintf(
                    '%02d-rekap-perkembangan-%s-%s-%s.pdf',
                    $index + 1,
                    $studentIdentifier,
                    Str::slug($student->nama_siswa),
                    Str::slug(str_replace('/', '-', $period)),
                );

                $zip->addFromString(
                    $entryName,
                    Pdf::loadView('dev.gpk.pdf.recap-document', $document)
                        ->setPaper('a4', 'portrait')
                        ->output(),
                );
            }

            $zip->close();
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($temporaryPath);

            throw $exception;
        }

        $classSuffix = $level !== '' ? '-kelas-'.Str::lower($level) : '';
        $filename = 'rekap-semua-siswa'.$classSuffix.'-'.Str::slug(str_replace('/', '-', $period)).'.zip';

        return response()->download($temporaryPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function exportRecap(Request $request, Siswa $siswa): StreamedResponse
    {
        $this->gpk($request);
        abort_unless($siswa->status_siswa === 'Aktif', 404);
        $schoolYears = $this->schoolYears();
        [, , $period, $periodStart, $periodEnd] = $this->periodContext($request, $schoolYears);
        $records = $this->laporanBulananService->recordsForStudent($siswa, $periodStart, $periodEnd);
        $filename = 'rekap-'.Str::slug($siswa->nama_siswa).'-'.Str::slug($period).'.csv';

        return response()->streamDownload(function () use ($siswa, $period, $records) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Rekap Perkembangan Siswa'], ';');
            fputcsv($output, ['Siswa', $siswa->nama_siswa], ';');
            fputcsv($output, ['Periode', $period], ';');
            fputcsv($output, [], ';');
            fputcsv($output, ['Tanggal', 'Sumber', 'Rekomendasi / Tindak Lanjut', 'Ringkasan Peristiwa'], ';');

            foreach ($records as $record) {
                fputcsv($output, [
                    $record['date']->format('d-m-Y'),
                    $record['source'],
                    $record['recommendation'],
                    $record['summary'],
                ], ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function previewRecapPdf(Request $request, Siswa $siswa): View
    {
        $gpk = $this->gpk($request);
        $document = $this->recapPdfData($request, $siswa, $gpk);

        return view('dev.gpk.pdf.recap-preview', $this->context($gpk) + $document);
    }

    public function downloadRecapPdf(Request $request, Siswa $siswa): Response
    {
        $gpk = $this->gpk($request);
        $document = $this->recapPdfData($request, $siswa, $gpk);
        $filename = 'rekap-perkembangan-'.Str::slug($siswa->nama_siswa).'-'.Str::slug($document['period']).'.pdf';

        return Pdf::loadView('dev.gpk.pdf.recap-document', $document)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function reports(Request $request): View
    {
        $gpk = $this->gpk($request);
        $this->laporanBulananService->synchronizeCompletedMonths();
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->periodContext($request, $schoolYears);
        $reports = LaporanBulanan::query()->with('publikator')
            ->whereBetween('periode_bulan', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->latest('periode_bulan')->latest('id_laporan_bulanan')
            ->paginate($this->perPage($request))->withQueryString();

        return view('dev.gpk.reports.index', $this->context($gpk) + [
            'reports' => $reports,
            'schoolYears' => $schoolYears,
            'period' => $period,
            'filters' => ['semester' => $semester, 'tahun_ajaran' => $schoolYear],
        ]);
    }

    public function report(Request $request, LaporanBulanan $laporan): View
    {
        $gpk = $this->gpk($request);
        $laporan->load('publikator');
        $students = collect($laporan->data_laporan['students'] ?? []);
        $filters = $request->validate(['siswa' => 'nullable|integer|min:1']);
        $selectedStudent = null;
        if (! empty($filters['siswa'])) {
            $selectedStudent = $students->first(fn (array $student) => (int) ($student['id_siswa'] ?? 0) === (int) $filters['siswa']);
            abort_unless($selectedStudent, 404);
            $student = Siswa::query()->findOrFail($filters['siswa']);
            $periodStart = CarbonImmutable::parse($laporan->periode_bulan)->startOfMonth();
            $selectedStudent['records'] = $this->laporanBulananService
                ->recordsForStudent($student, $periodStart, $periodStart->endOfMonth())
                ->map(fn (array $record) => [...$record, 'date' => $record['date']->toDateString()])
                ->all();
        }

        return view('dev.gpk.reports.show', $this->context($gpk) + compact('laporan', 'students', 'selectedStudent'));
    }

    public function previewReportPdf(Request $request, LaporanBulanan $laporan): View
    {
        $gpk = $this->gpk($request);
        $document = $this->reportPdfData($laporan, $gpk);

        return view('dev.gpk.pdf.report-preview', $this->context($gpk) + $document);
    }

    public function downloadReportPdf(Request $request, LaporanBulanan $laporan): Response
    {
        $gpk = $this->gpk($request);
        $document = $this->reportPdfData($laporan, $gpk);
        $filename = 'laporan-perkembangan-'.Str::slug($document['period']).'.pdf';

        return Pdf::loadView('dev.gpk.pdf.report-document', $document)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function publishReport(Request $request, LaporanBulanan $laporan): RedirectResponse
    {
        $gpk = $this->gpk($request);

        try {
            $this->laporanBulananService->publish($laporan, $gpk);
        } catch (DomainException $exception) {
            return back()->withErrors(['publikasi' => $exception->getMessage()]);
        }

        return back()->with('success', 'Laporan berhasil dipublikasikan.');
    }

    public function parents(Request $request): View
    {
        $gpk = $this->gpk($request);
        $search = trim((string) $request->query('cari'));
        $level = $this->level($request);
        $relations = RelasiOrangTuaSiswa::query()
            ->with(['siswa.kelas', 'orangTua.pengguna'])
            ->whereHas('siswa', fn ($students) => $students
                ->whereIn('id_siswa', $this->assignedStudentIds($gpk))
                ->when($search !== '', fn ($query) => $query->where('nama_siswa', 'like', "%{$search}%"))
                ->when($level !== '', fn ($query) => $query->whereHas('kelas', fn ($classes) => $classes->where('tingkat', $level))))
            ->orderBy('id_siswa')
            ->paginate($this->perPage($request))->withQueryString();

        return view('dev.gpk.parents.index', $this->context($gpk) + [
            'relations' => $relations,
            'filters' => ['cari' => $search, 'tingkat' => $level],
        ]);
    }

    private function assignedStudents(Pengguna $gpk): Builder
    {
        return Siswa::query()->where('status_siswa', 'Aktif')
            ->whereHas('relasiPendampingSiswa', fn ($query) => $query->where('id_pengguna', $gpk->getKey()));
    }

    private function recapPdfData(Request $request, Siswa $siswa, Pengguna $gpk): array
    {
        abort_unless($siswa->status_siswa === 'Aktif', 404);
        $siswa->load('kelas');
        $schoolYears = $this->schoolYears();
        [$semester, $schoolYear, $period, $periodStart, $periodEnd] = $this->periodContext($request, $schoolYears);

        return [
            'siswa' => $siswa,
            'records' => $this->laporanBulananService->recordsForStudent($siswa, $periodStart, $periodEnd),
            'semester' => $semester,
            'schoolYear' => $schoolYear,
            'period' => $period,
            ...$this->signatureData($gpk),
        ];
    }

    private function reportPdfData(LaporanBulanan $laporan, Pengguna $gpk): array
    {
        return [
            'laporan' => $laporan,
            'students' => collect($laporan->data_laporan['students'] ?? []),
            'period' => $laporan->periode_bulan->locale('id')->translatedFormat('F Y'),
            ...$this->signatureData($gpk),
        ];
    }

    private function signatureData(Pengguna $gpk): array
    {
        $headmaster = Pengguna::query()
            ->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($roles) => $roles->where('nama_role', 'Kepala Sekolah'))
            ->orderBy('id_pengguna')
            ->first();

        return [
            'schoolName' => 'SMAN 4 BANJARMASIN',
            'printDate' => now('Asia/Makassar')->locale('id')->translatedFormat('d F Y'),
            'headmaster' => $headmaster,
            'documentGpk' => $gpk,
        ];
    }

    private function assignedStudentIds(Pengguna $gpk): Collection
    {
        return $this->assignedStudents($gpk)->pluck('id_siswa');
    }

    private function ensureAssigned(Pengguna $gpk, Siswa $siswa): void
    {
        abort_unless($this->assignedStudents($gpk)->whereKey($siswa->getKey())->exists(), 404);
    }

    private function notesFor(?int $studentId = null): Collection
    {
        $development = CatatanPerkembangan::query()
            ->with(['siswa.kelas', 'pembuat.role', 'mataPelajaran', 'peninjauGpk', 'verifikasi'])
            ->when($studentId !== null, fn ($query) => $query->where('id_siswa', $studentId))
            ->whereHas('pembuat.role', fn ($roles) => $roles->where('nama_role', 'Guru Mapel'))
            ->where('status_pengajuan', '!=', 'Draft')->get()
            ->map(fn ($note) => $this->presentNote('perkembangan', $note));
        $assistance = CatatanPendampingan::query()
            ->with(['siswa.kelas', 'pembuat.role', 'peninjauGpk', 'verifikasi'])
            ->when($studentId !== null, fn ($query) => $query->where('id_siswa', $studentId))
            ->whereHas('pembuat.role', fn ($roles) => $roles->where('nama_role', 'Tim Inklusi'))
            ->where('status_pengajuan', '!=', 'Draft')->get()
            ->map(fn ($note) => $this->presentNote('pendampingan', $note));

        return $development->concat($assistance);
    }

    private function presentNote(string $type, $note): array
    {
        $decision = $note->verifikasi->first(fn ($verification) => (int) $verification->versi_pengajuan === (int) $note->versi_pengajuan
        );
        $source = $type === 'perkembangan'
            ? 'Guru Mata Pelajaran · '.($note->pembuat?->nama ?: 'Tidak diketahui').' · '.($note->mataPelajaran?->nama_mapel ?: 'Tanpa mapel')
            : 'Tim Inklusi / Pendamping · '.($note->pembuat?->nama ?: 'Tidak diketahui');
        $expectedRole = $type === 'perkembangan' ? 'Guru Mapel' : 'Tim Inklusi';
        $sourceRoleIsValid = $note->pembuat?->role?->nama_role === $expectedRole;

        return [
            'type' => $type,
            'note' => $note,
            'student' => $note->siswa,
            'date' => $note->tanggal_catatan,
            'sort_date' => ($note->tanggal_catatan?->format('Y-m-d') ?? '').'-'.str_pad((string) $note->getKey(), 10, '0', STR_PAD_LEFT),
            'source' => $source,
            'status' => $note->status_pengajuan === 'Dikirim' && (int) $note->versi_ditinjau === (int) $note->versi_pengajuan && $note->id_peninjau_gpk
                ? 'Sedang Ditinjau · '.($note->peninjauGpk?->nama ?: 'GPK')
                : $note->status_pengajuan,
            'decision' => $decision,
            'is_pending' => $note->status_pengajuan === 'Dikirim' && $decision === null && $sourceRoleIsValid,
        ];
    }

    private function findNote(string $type, int $id)
    {
        abort_unless(in_array($type, ['perkembangan', 'pendampingan'], true), 404);

        return $type === 'perkembangan'
            ? CatatanPerkembangan::query()->findOrFail($id)
            : CatatanPendampingan::query()->findOrFail($id);
    }

    private function ensureReviewable(string $type, $note): void
    {
        $note->loadMissing('pembuat.role');
        $expectedRole = $type === 'perkembangan' ? 'Guru Mapel' : 'Tim Inklusi';

        abort_if($note->status_pengajuan === 'Draft' || $note->pembuat?->role?->nama_role !== $expectedRole, 404);
    }

    private function periodContext(Request $request, Collection $schoolYears): array
    {
        $now = CarbonImmutable::now();
        $defaultSchoolYear = TahunAjaran::aktif()?->nama ?: ($now->month >= 7
            ? $now->year.'/'.($now->year + 1)
            : ($now->year - 1).'/'.$now->year);
        $schoolYear = trim((string) $request->input('tahun_ajaran', ''));
        if (! preg_match('/^\d{4}\/\d{4}$/', $schoolYear) || ! $schoolYears->contains($schoolYear)) {
            $schoolYear = $schoolYears->contains($defaultSchoolYear)
                ? $defaultSchoolYear
                : (string) ($schoolYears->first() ?: $defaultSchoolYear);
        }
        $semester = strtolower((string) $request->input('semester', ''));
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = $now->month >= 7 ? 'ganjil' : 'genap';
        }

        return [$semester, $schoolYear, ...$this->periodRange($semester, $schoolYear)];
    }

    private function periodRange(string $semester, string $schoolYear): array
    {
        $master = TahunAjaran::query()->where('nama', $schoolYear)->first();
        if ($master) {
            $start = CarbonImmutable::parse($semester === 'ganjil' ? $master->mulai_ganjil : $master->mulai_genap)->startOfDay();
            $end = CarbonImmutable::parse($semester === 'ganjil' ? $master->selesai_ganjil : $master->selesai_genap)->endOfDay();

            return ['Semester '.ucfirst($semester).' '.$schoolYear, $start, $end];
        }

        [$startYear, $endYear] = array_map('intval', explode('/', $schoolYear));
        abort_unless($endYear === $startYear + 1, 422, 'Tahun ajaran tidak valid.');

        if ($semester === 'ganjil') {
            $start = CarbonImmutable::create($startYear, 7, 1)->startOfDay();
            $end = CarbonImmutable::create($startYear, 12, 31)->endOfDay();
        } else {
            $start = CarbonImmutable::create($endYear, 1, 1)->startOfDay();
            $end = CarbonImmutable::create($endYear, 6, 30)->endOfDay();
        }

        return ['Semester '.ucfirst($semester).' '.$schoolYear, $start, $end];
    }

    private function schoolYears(): Collection
    {
        $years = TahunAjaran::query()
            ->orderByRaw("status = 'Aktif' desc")
            ->orderByDesc('mulai_ganjil')
            ->pluck('nama');

        return $years->isNotEmpty()
            ? $years
            : Siswa::query()->where('status_siswa', 'Aktif')
                ->join('kelas', 'kelas.id_kelas', '=', 'siswa.id_kelas')
                ->distinct()->orderByDesc('kelas.tahun_ajaran')->pluck('kelas.tahun_ajaran');
    }

    private function verifiedNotesInPeriod(Builder $query, string $type, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $table = $type === 'perkembangan' ? 'catatan_perkembangan' : 'catatan_pendampingan';
        $role = $type === 'perkembangan' ? 'Guru Mapel' : 'Tim Inklusi';

        return $query->whereBetween('tanggal_catatan', [$start->toDateString(), $end->toDateString()])
            ->where('status_pengajuan', 'Terverifikasi')
            ->whereHas('pembuat.role', fn ($roles) => $roles->where('nama_role', $role))
            ->whereHas('verifikasi', fn ($verifications) => $verifications
                ->where('status_verifikasi', 'Terverifikasi')
                ->whereColumn('verifikasi_catatan.versi_pengajuan', $table.'.versi_pengajuan'));
    }

    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $perPage = $this->perPage($request);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function gpk(Request $request): Pengguna
    {
        return $request->attributes->get('gpk');
    }

    private function level(Request $request): string
    {
        $level = strtoupper((string) $request->query('tingkat'));

        return in_array($level, ['X', 'XI', 'XII'], true) ? $level : '';
    }

    private function perPage(Request $request): int
    {
        $perPage = $request->integer('per_halaman');

        return in_array($perPage, [10, 30, 50, 100], true) ? $perPage : 10;
    }

    private function context(Pengguna $gpk): array
    {
        $initials = collect(preg_split('/\s+/', trim($gpk->nama)))
            ->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->join('');

        return [
            'role' => 'gpk',
            'profile' => ['label' => $gpk->jabatan_gpk ? $gpk->jabatan_gpk.' GPK' : 'GPK', 'name' => $gpk->nama, 'initials' => $initials ?: 'GP'],
            'previewRoles' => [
                'admin' => ['label' => 'Super Admin'],
                'guru-mapel' => ['label' => 'Guru Mata Pelajaran'],
                'tim-inklusi' => ['label' => 'Tim Inklusi'],
                'gpk' => ['label' => 'GPK'],
                'kepala-sekolah' => ['label' => 'Kepala Sekolah'],
                'orang-tua' => ['label' => 'Orang Tua'],
            ],
            'gpk' => $gpk,
        ];
    }
}
