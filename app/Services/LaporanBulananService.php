<?php

namespace App\Services;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\LaporanBulanan;
use App\Models\Siswa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LaporanBulananService
{
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
            $report = LaporanBulanan::query()->firstOrNew([
                'periode_bulan' => $month->toDateString(),
            ]);
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
            ->with(['pembuat', 'mataPelajaran'])
            ->get()
            ->map(fn (CatatanPerkembangan $note) => [
                'type' => 'perkembangan',
                'date' => $note->tanggal_catatan,
                'source' => 'Guru Mata Pelajaran'.($note->mataPelajaran ? ' · '.$note->mataPelajaran->nama_mapel : ''),
                'recommendation' => $note->tindakan ?: ($note->catatan_tambahan ?: '—'),
                'summary' => $note->hasil_tindakan ?: ($note->kendala_peristiwa ?: $note->materi_kegiatan),
            ]);

        $assistance = $this->verifiedAssistanceQuery($start, $end)
            ->where('id_siswa', $student->getKey())
            ->with('pembuat')
            ->get()
            ->map(fn (CatatanPendampingan $note) => [
                'type' => 'pendampingan',
                'date' => $note->tanggal_catatan,
                'source' => 'Tim Inklusi / Pendamping',
                'recommendation' => $note->tindak_lanjut ?: '—',
                'summary' => $note->kegiatan_pendampingan ?: ($note->kendala ?: '—'),
            ]);

        return $development->concat($assistance)
            ->sortByDesc(fn ($record) => $record['date']->format('Y-m-d'))
            ->values();
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
