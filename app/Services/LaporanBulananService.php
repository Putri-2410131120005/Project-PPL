<?php

namespace App\Services;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\AktivitasGpk;
use App\Models\LaporanBulanan;
use App\Models\LaporanPerkembangan;
use App\Models\Pengguna;
use App\Models\RekapPerkembangan;
use App\Models\Siswa;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LaporanBulananService
{
    public function publish(LaporanBulanan $report, Pengguna $gpk): void
    {
        DB::transaction(function () use ($report, $gpk): void {
            $locked = LaporanBulanan::query()->lockForUpdate()->findOrFail($report->getKey());
            if ($locked->status_laporan !== 'Belum Tersedia' || $locked->id_publikator !== null || $locked->tanggal_publikasi !== null) {
                throw new DomainException('Laporan sudah dipublikasikan atau status publikasinya tidak konsisten.');
            }
            $locked->forceFill([
                'status_laporan' => 'Tersedia',
                'id_publikator' => $gpk->getKey(),
                'tanggal_publikasi' => now(),
            ])->save();
            AktivitasGpk::query()->create([
                'id_gpk' => $gpk->getKey(),
                'id_laporan_bulanan' => $locked->getKey(),
                'jenis_aktivitas' => 'Publikasi Laporan',
                'keterangan' => 'Laporan '.$locked->periode_bulan->locale('id')->translatedFormat('F Y').' dipublikasikan.',
                'tanggal_aktivitas' => now(),
            ]);
            $this->synchronizeOfficialProjection($locked, $gpk);
        });
    }

    /** Keep the monthly report compatible with Principal and Parent projections. */
    public function synchronizeOfficialProjection(LaporanBulanan $report, Pengguna $gpk): void
    {
        if ($report->status_laporan !== 'Tersedia' || (int) $report->id_publikator !== (int) $gpk->getKey()) {
            throw new DomainException('Hanya laporan bulanan yang sudah dipublikasikan GPK yang dapat diproyeksikan.');
        }

        $start = CarbonImmutable::parse($report->periode_bulan, 'Asia/Makassar')->startOfMonth();
        $end = $start->endOfMonth();
        $period = $start->locale('id')->translatedFormat('F Y');
        $snapshots = collect($report->data_laporan['students'] ?? [])->keyBy('id_siswa');
        $studentIds = $this->verifiedDevelopmentQuery($start, $end)->pluck('id_siswa')
            ->concat($this->verifiedAssistanceQuery($start, $end)->pluck('id_siswa'))->unique();

        foreach (Siswa::query()->whereIn('id_siswa', $studentIds)->get() as $student) {
            $snapshot = $snapshots->get($student->getKey(), []);
            $summaries = collect($snapshot['ringkasan'] ?? [])->filter()->values();
            $summary = $summaries->isNotEmpty()
                ? $summaries->map(fn ($text) => '• '.$text)->implode("\n")
                : 'Belum ada ringkasan perkembangan pada periode ini.';
            $recap = RekapPerkembangan::query()->firstOrNew([
                'id_siswa' => $student->getKey(), 'id_pembuat' => $gpk->getKey(), 'periode' => $period,
            ]);
            $recap->fill(['ringkasan_perkembangan' => $summary, 'tanggal_rekap' => $end->endOfDay()])->save();

            $developmentIds = $this->verifiedDevelopmentQuery($start, $end)->where('id_siswa', $student->getKey())
                ->pluck('id_catatan_perkembangan');
            foreach ($developmentIds as $id) {
                $recap->sumberCatatan()->firstOrCreate(['id_catatan_perkembangan' => $id], ['id_catatan_pendampingan' => null]);
            }
            $assistanceIds = $this->verifiedAssistanceQuery($start, $end)->where('id_siswa', $student->getKey())
                ->pluck('id_catatan_pendampingan');
            foreach ($assistanceIds as $id) {
                $recap->sumberCatatan()->firstOrCreate(['id_catatan_pendampingan' => $id], ['id_catatan_perkembangan' => null]);
            }

            $official = LaporanPerkembangan::query()->firstOrNew(['id_rekap' => $recap->getKey(), 'periode' => $period]);
            $official->forceFill([
                'id_pembuat' => $gpk->getKey(), 'isi_laporan' => $summary,
                'tanggal_laporan' => $end->endOfDay(), 'status_laporan' => 'Tersedia',
                'id_publikator' => $gpk->getKey(), 'tanggal_publikasi' => $report->tanggal_publikasi,
            ])->save();
        }
    }

    public function synchronizeCompletedMonths(): void
    {
        $today = now('Asia/Makassar')->toDateString();
        $months = $this->verifiedDevelopmentQuery()->pluck('tanggal_catatan')
            ->concat($this->verifiedAssistanceQuery()->pluck('tanggal_catatan'))
            ->map(fn ($date) => CarbonImmutable::parse($date, 'Asia/Makassar')->startOfMonth()->toDateString())
            ->filter(fn (string $month) => CarbonImmutable::parse($month, 'Asia/Makassar')->endOfMonth()->toDateString() <= $today)
            ->unique()
            ->sortDesc();

        foreach ($months as $monthDate) {
            $month = CarbonImmutable::parse($monthDate, 'Asia/Makassar')->startOfMonth();
            $report = LaporanBulanan::query()->whereDate('periode_bulan', $month->toDateString())->first()
                ?? new LaporanBulanan(['periode_bulan' => $month->toDateString()]);
            if ($report->exists && $report->status_laporan === 'Tersedia') {
                continue;
            }

            $snapshot = $this->snapshot($month, $month->endOfMonth());
            $report->forceFill([
                'jumlah_siswa' => count($snapshot['students']),
                'jumlah_catatan' => collect($snapshot['students'])->sum('jumlah_catatan'),
                'data_laporan' => $snapshot,
                'status_laporan' => 'Belum Tersedia',
                'tanggal_dibuat' => $month->endOfMonth()->endOfDay(),
                'id_publikator' => null,
                'tanggal_publikasi' => null,
            ])->save();
        }
    }

    public function recordsForStudent(Siswa $student, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $development = $this->verifiedDevelopmentQuery($start, $end)
            ->where('id_siswa', $student->getKey())
            ->with(['pembuat', 'mataPelajaran', 'verifikasi.verifikator'])
            ->get()
            ->map(function (CatatanPerkembangan $note) {
                $verification = $this->currentVerification($note);

                return [
                    'type' => 'perkembangan',
                    'date' => $note->tanggal_catatan,
                    'source' => 'Guru Mata Pelajaran'.($note->mataPelajaran ? ' · '.$note->mataPelajaran->nama_mapel : ''),
                    'author' => $note->pembuat?->nama,
                    'recommendation' => $note->tindakan ?: ($note->catatan_tambahan ?: '—'),
                    'verification_recommendation' => $verification?->catatan_verifikasi,
                    'verified_by' => $verification?->verifikator?->nama,
                    'verification_date' => $verification?->tanggal_verifikasi?->toDateTimeString(),
                    'summary' => $note->hasil_tindakan ?: ($note->kendala_peristiwa ?: $note->materi_kegiatan),
                ];
            });

        $assistance = $this->verifiedAssistanceQuery($start, $end)
            ->where('id_siswa', $student->getKey())
            ->with(['pembuat', 'verifikasi.verifikator'])
            ->get()
            ->map(function (CatatanPendampingan $note) {
                $verification = $this->currentVerification($note);

                return [
                    'type' => 'pendampingan',
                    'date' => $note->tanggal_catatan,
                    'source' => 'Tim Inklusi / Pendamping',
                    'author' => $note->pembuat?->nama,
                    'recommendation' => $note->tindak_lanjut ?: '—',
                    'verification_recommendation' => $verification?->catatan_verifikasi,
                    'verified_by' => $verification?->verifikator?->nama,
                    'verification_date' => $verification?->tanggal_verifikasi?->toDateTimeString(),
                    'summary' => $note->kegiatan_pendampingan ?: ($note->kendala ?: '—'),
                ];
            });

        return $development->concat($assistance)
            ->sortByDesc(fn ($record) => $record['date']->format('Y-m-d'))
            ->values();
    }

    private function currentVerification(CatatanPerkembangan|CatatanPendampingan $note)
    {
        return $note->verifikasi
            ->where('status_verifikasi', 'Terverifikasi')
            ->where('versi_pengajuan', $note->versi_pengajuan)
            ->sortByDesc('tanggal_verifikasi')
            ->first();
    }

    public function snapshot(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $students = Siswa::query()->where('status_siswa', 'Aktif')->with('kelas')->orderBy('nama_siswa')->get();

        return [
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'students' => $students->map(function (Siswa $student) use ($start, $end) {
                $records = $this->recordsForStudent($student, $start, $end);

                return [
                    'id_siswa' => $student->getKey(),
                    'nama_siswa' => $student->nama_siswa,
                    'kelas' => $student->kelas?->nama_kelas ?: '—',
                    'jumlah_catatan' => $records->count(),
                    'ringkasan' => $records->pluck('summary')->filter()->values()->all(),
                    'records' => $records->map(fn ($record) => [
                        ...$record,
                        'date' => $record['date']->toDateString(),
                    ])->all(),
                ];
            })->all(),
        ];
    }

    private function verifiedDevelopmentQuery(?CarbonImmutable $start = null, ?CarbonImmutable $end = null): Builder
    {
        return CatatanPerkembangan::query()
            ->when($start && $end, fn (Builder $query) => $query->whereBetween('tanggal_catatan', [$start->toDateString(), $end->toDateString()]))
            ->where('status_pengajuan', 'Terverifikasi')
            ->whereHas('pembuat.role', fn ($roles) => $roles->where('nama_role', 'Guru Mapel'))
            ->whereHas('verifikasi', fn ($verifications) => $verifications
                ->where('status_verifikasi', 'Terverifikasi')
                ->whereColumn('verifikasi_catatan.versi_pengajuan', 'catatan_perkembangan.versi_pengajuan'));
    }

    private function verifiedAssistanceQuery(?CarbonImmutable $start = null, ?CarbonImmutable $end = null): Builder
    {
        return CatatanPendampingan::query()
            ->when($start && $end, fn (Builder $query) => $query->whereBetween('tanggal_catatan', [$start->toDateString(), $end->toDateString()]))
            ->where('status_pengajuan', 'Terverifikasi')
            ->whereHas('pembuat.role', fn ($roles) => $roles->where('nama_role', 'Tim Inklusi'))
            ->whereHas('verifikasi', fn ($verifications) => $verifications
                ->where('status_verifikasi', 'Terverifikasi')
                ->whereColumn('verifikasi_catatan.versi_pengajuan', 'catatan_pendampingan.versi_pengajuan'));
    }
}
