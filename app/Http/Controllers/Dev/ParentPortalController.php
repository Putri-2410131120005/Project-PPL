<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\LaporanPerkembangan;
use App\Models\Pengguna;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ParentPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $parent = $this->parent();
        $student = $this->studentFor($parent);
        $notes = $student ? $this->publishedNotes($student) : collect();
        $latestReport = $student ? $this->availableReports($student)->first() : null;

        return view('dev.parent.dashboard', array_merge($this->viewContext($parent, $student), [
            'student' => $student,
            'latestNotes' => $notes->take(3),
            'latestNote' => $notes->first(),
            'latestReport' => $latestReport,
            'companion' => $student?->relasiPendampingSiswa()->with('pendamping')->first()?->pendamping,
        ]));
    }

    public function notes(Request $request): View
    {
        $parent = $this->parent();
        $student = $this->studentFor($parent);
        $date = $request->date('tanggal')?->toDateString();
        $month = (string) $request->query('bulan');
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : '';
        $notes = $student ? $this->publishedNotes($student) : collect();

        if ($date) {
            $notes = $notes->where('date_key', $date)->values();
        } elseif ($month !== '') {
            $notes = $notes->filter(fn ($note) => Str::startsWith($note['date_key'], $month))->values();
        }

        return view('dev.parent.notes.index', array_merge($this->viewContext($parent, $student), [
            'student' => $student,
            'notes' => $this->paginate($request, $notes),
            'filters' => ['tanggal' => $date, 'bulan' => $month],
        ]));
    }

    public function note(Request $request, string $type, int $id): View
    {
        $parent = $this->parent();
        $student = $this->studentFor($parent);
        abort_unless($student, 404);
        $note = $this->findPublishedNote($student, $type, $id);

        return view('dev.parent.notes.show', array_merge($this->viewContext($parent, $student), compact('student', 'note')));
    }

    public function reports(Request $request): View
    {
        $parent = $this->parent();
        $student = $this->studentFor($parent);
        $semester = in_array($request->query('semester'), ['Ganjil', 'Genap'], true)
            ? $request->query('semester')
            : $this->currentSemester();
        $reports = $student ? $this->availableReports($student) : collect();
        $periodOptions = $reports->pluck('periode')->filter()->unique()->values();
        $period = trim((string) $request->query('periode'));

        if ($period !== '' && ! $periodOptions->contains($period)) {
            $period = '';
        }

        $report = $reports
            ->when($period !== '', fn ($items) => $items->where('periode', $period))
            ->first(fn ($item) => Str::contains(Str::lower($item->periode), Str::lower($semester)));
        $sources = $report && $student ? $this->reportSources($report, $student) : collect();

        return view('dev.parent.reports.index', array_merge($this->viewContext($parent, $student), [
            'student' => $student,
            'report' => $report,
            'sources' => $sources,
            'semester' => $semester,
            'period' => $period,
            'periodOptions' => $periodOptions,
            'periodLabel' => $this->periodLabel($semester, $period ?: $report?->periode),
        ]));
    }

    private function publishedNotes(Siswa $student): Collection
    {
        $development = CatatanPerkembangan::query()
            ->dipublikasikan()
            ->with(['pembuat', 'mataPelajaran'])
            ->where('id_siswa', $student->getKey())
            ->get()
            ->map(fn ($note) => $this->developmentNote($note));
        $assistance = CatatanPendampingan::query()
            ->dipublikasikan()
            ->with('pembuat')
            ->where('id_siswa', $student->getKey())
            ->get()
            ->map(fn ($note) => $this->assistanceNote($note));

        return $development->concat($assistance)
            ->sortByDesc(fn ($note) => $note['date_key'].'-'.str_pad((string) $note['id'], 10, '0', STR_PAD_LEFT))
            ->values();
    }

    private function findPublishedNote(Siswa $student, string $type, int $id): array
    {
        if ($type === 'perkembangan') {
            $note = CatatanPerkembangan::query()
                ->dipublikasikan()
                ->with(['pembuat', 'mataPelajaran'])
                ->where('id_siswa', $student->getKey())
                ->findOrFail($id);

            return $this->developmentNote($note);
        }

        if ($type === 'pendampingan') {
            $note = CatatanPendampingan::query()
                ->dipublikasikan()
                ->with('pembuat')
                ->where('id_siswa', $student->getKey())
                ->findOrFail($id);

            return $this->assistanceNote($note);
        }

        abort(404);
    }

    private function developmentNote(CatatanPerkembangan $note): array
    {
        return [
            'id' => $note->getKey(),
            'type' => 'perkembangan',
            'date' => $note->tanggal_catatan,
            'date_key' => $note->tanggal_catatan->toDateString(),
            'source' => 'Guru Mata Pelajaran',
            'source_short' => 'Guru Mapel'.($note->mataPelajaran ? ' — '.$note->mataPelajaran->nama_mapel : ''),
            'author' => $note->pembuat?->nama,
            'subject' => $note->mataPelajaran?->nama_mapel,
            'summary' => $note->hasil_tindakan ?: $note->materi_kegiatan,
            'recommendation' => $note->tindakan ?: $note->catatan_tambahan,
            'fields' => collect([
                'Materi / Kegiatan' => $note->materi_kegiatan,
                'Kendala / Peristiwa' => $note->kendala_peristiwa,
                'Tindakan' => $note->tindakan,
                'Hasil Tindakan' => $note->hasil_tindakan,
                'Catatan Tambahan' => $note->catatan_tambahan,
            ])->filter(fn ($value) => filled($value)),
        ];
    }

    private function assistanceNote(CatatanPendampingan $note): array
    {
        return [
            'id' => $note->getKey(),
            'type' => 'pendampingan',
            'date' => $note->tanggal_catatan,
            'date_key' => $note->tanggal_catatan->toDateString(),
            'source' => 'Tim Inklusi / Guru Pendamping',
            'source_short' => 'Tim Inklusi — Pendampingan',
            'author' => $note->pembuat?->nama,
            'subject' => null,
            'summary' => $note->kegiatan_pendampingan,
            'recommendation' => $note->tindak_lanjut,
            'fields' => collect([
                'Catatan Pendampingan' => $note->kegiatan_pendampingan,
                'Kendala / Kebutuhan Dukungan' => $note->kendala,
                'Tindak Lanjut Pendampingan' => $note->tindak_lanjut,
            ])->filter(fn ($value) => filled($value)),
        ];
    }

    private function availableReports(Siswa $student): Collection
    {
        return LaporanPerkembangan::query()
            ->tersedia()
            ->with(['rekap.sumberCatatan', 'publikator'])
            ->whereHas('rekap', fn ($query) => $query->where('id_siswa', $student->getKey()))
            ->latest('tanggal_publikasi')
            ->get();
    }

    private function reportSources(LaporanPerkembangan $report, Siswa $student): Collection
    {
        $allowed = $this->publishedNotes($student)->keyBy(fn ($note) => $note['type'].'-'.$note['id']);

        return $report->rekap->sumberCatatan
            ->map(function ($source) use ($allowed) {
                $key = $source->id_catatan_perkembangan
                    ? 'perkembangan-'.$source->id_catatan_perkembangan
                    : 'pendampingan-'.$source->id_catatan_pendampingan;

                return $allowed->get($key);
            })
            ->filter()
            ->sortByDesc('date_key')
            ->values();
    }

    private function studentFor(Pengguna $parent): ?Siswa
    {
        return Siswa::query()
            ->with('kelas')
            ->whereHas('relasiOrangTuaSiswa.orangTua', fn ($query) => $query->where('id_pengguna', $parent->getKey()))
            ->orderBy('id_siswa')
            ->first();
    }

    private function paginate(Request $request, Collection $items): LengthAwarePaginator
    {
        $perPage = in_array($request->integer('per_halaman'), [10, 30, 50, 100], true)
            ? $request->integer('per_halaman')
            : 10;
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function currentSemester(): string
    {
        return now()->month >= 7 ? 'Ganjil' : 'Genap';
    }

    private function periodLabel(string $semester, ?string $period): string
    {
        if ($period) {
            return $period;
        }

        $startYear = now()->month >= 7 ? now()->year : now()->year - 1;

        return $semester.' '.$startYear.'/'.($startYear + 1);
    }

    private function parent(): Pengguna
    {
        return Auth::guard('parent')->user();
    }

    private function viewContext(Pengguna $parent, ?Siswa $student): array
    {
        $initials = collect(preg_split('/\s+/', trim($parent->nama)))
            ->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->join('');

        return [
            'role' => 'orang-tua',
            'profile' => ['label' => 'Orang Tua/Wali', 'name' => $parent->nama, 'initials' => $initials ?: 'OT'],
            'previewRoles' => [
                'orang-tua' => [
                    'label' => 'Orang Tua/Wali',
                    'name' => $parent->nama,
                    'initials' => $initials ?: 'OT',
                    'secondary' => $student ? 'Wali '.$student->nama_siswa : 'Belum ada anak terhubung',
                ],
            ],
            'parent' => $parent,
        ];
    }
}
