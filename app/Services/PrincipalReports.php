<?php

namespace App\Services;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\LaporanPerkembangan;
use App\Models\RekapPerkembangan;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Read-only development projection. No report creation, verification or publication. */
class PrincipalReports
{
    public function students(): Builder
    {
        return Siswa::where('nis', 'like', 'DEMO-A16-%')->where('nama_siswa', 'like', 'DATA DEMO %')
            ->whereHas('kelas', fn ($q) => $q->where('nama_kelas', 'like', 'DATA DEMO %'));
    }

    private function demoAuthor(Builder $q, ?string $role = null): Builder
    {
        return $q->where('nama', 'like', 'DATA DEMO %')->where('email', 'like', '%@example.test')
            ->when($role, fn ($q) => $q->whereHas('role', fn ($r) => $r->where('nama_role', $role)));
    }

    public function notes(string $source, bool $published = true): Builder
    {
        $q = $source === 'perkembangan' ? CatatanPerkembangan::query() : CatatanPendampingan::query();
        $q->whereIn('id_siswa', $this->students()->select('id_siswa'))
            ->whereHas('pembuat', fn ($a) => $this->demoAuthor($a, $source === 'perkembangan' ? 'Guru Mapel' : 'Tim Inklusi'));
        if ($source === 'perkembangan') {
            $q->whereHas('mataPelajaran', fn ($m) => $m->where('nama_mapel', 'like', 'DATA DEMO %'));
        }
        $table = $q->getModel()->getTable();
        $q->where('status_pengajuan', 'Terverifikasi')->whereHas('verifikasi', function ($v) use ($table, $published) {
            $v->where('status_verifikasi', 'Terverifikasi')->whereColumn('verifikasi_catatan.versi_pengajuan', $table.'.versi_pengajuan')
                ->whereHas('verifikator', fn ($a) => $this->demoAuthor($a, 'GPK'));
            if ($published) {
                $v->whereNotNull('tanggal_publikasi')->whereHas('publikator', fn ($a) => $this->demoAuthor($a, 'GPK'));
            }
        });

        return $q;
    }

    private function eligibleRecaps(): Builder
    {
        return RekapPerkembangan::whereIn('id_siswa', $this->students()->select('id_siswa'))
            ->whereHas('pembuat', fn ($a) => $this->demoAuthor($a, 'GPK'))
            // Fail closed: unpublished, malformed or cross-student links invalidate the official projection.
            ->whereDoesntHave('sumberCatatan', function ($link) {
                $link->whereNot(function ($valid) {
                    $valid->where(function ($one) {
                        $one->whereNotNull('id_catatan_perkembangan')->whereNull('id_catatan_pendampingan')
                            ->whereHas('catatanPerkembangan', fn ($note) => $note->whereIn('id_catatan_perkembangan', $this->notes('perkembangan')->select('id_catatan_perkembangan'))
                                ->whereColumn('catatan_perkembangan.id_siswa', 'rekap_perkembangan.id_siswa'));
                    })->orWhere(function ($two) {
                        $two->whereNull('id_catatan_perkembangan')->whereNotNull('id_catatan_pendampingan')
                            ->whereHas('catatanPendampingan', fn ($note) => $note->whereIn('id_catatan_pendampingan', $this->notes('pendampingan')->select('id_catatan_pendampingan'))
                                ->whereColumn('catatan_pendampingan.id_siswa', 'rekap_perkembangan.id_siswa'));
                    });
                });
            });
    }

    public function reports(array $filters = []): Builder
    {
        $q = LaporanPerkembangan::where('status_laporan', 'Tersedia')->whereNotNull('tanggal_publikasi')
            ->whereHas('publikator', fn ($a) => $this->demoAuthor($a, 'GPK'))
            ->whereHas('pembuat', fn ($a) => $this->demoAuthor($a, 'GPK'))
            ->whereIn('laporan_perkembangan.id_rekap', $this->eligibleRecaps()->select('id_rekap'));
        if (! empty($filters['periode'])) {
            $q->where('laporan_perkembangan.periode', $filters['periode']);
        }
        if (! empty($filters['bulan'])) {
            $q->whereMonth('tanggal_laporan', $filters['bulan']);
        }
        if (! empty($filters['tahun'])) {
            $q->whereYear('tanggal_laporan', $filters['tahun']);
        }

        return $q->whereHas('rekap.siswa', fn ($s) => $this->studentFilters($s, $filters));
    }

    public function recaps(array $filters = []): Builder
    {
        $q = $this->eligibleRecaps()->whereIn('id_rekap', $this->reports()->select('id_rekap'));
        if (! empty($filters['periode'])) {
            $q->where('periode', $filters['periode']);
        }

        return $q->whereHas('siswa', fn ($s) => $this->studentFilters($s, $filters));
    }

    public function studentFilters(Builder $q, array $filters): Builder
    {
        if (! empty($filters['q'])) {
            $q->where('nama_siswa', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['siswa'])) {
            $q->where('id_siswa', $filters['siswa']);
        }
        if (! empty($filters['tingkat']) && $filters['tingkat'] !== 'Semua') {
            $q->whereHas('kelas', fn ($c) => $c->where('tingkat', $filters['tingkat']));
        }
        if (! empty($filters['tahun_ajaran'])) {
            $q->whereHas('kelas', fn ($c) => $c->where('tahun_ajaran', $filters['tahun_ajaran']));
        }

        return $q;
    }

    public function detail(int $id): RekapPerkembangan
    {
        return $this->loadDetails($this->recaps()->whereKey($id))->firstOrFail();
    }

    public function loadDetails(Builder $query): Builder
    {
        return $query->with(['siswa.kelas', 'pembuat', 'sumberCatatan.catatanPerkembangan.pembuat',
            'sumberCatatan.catatanPerkembangan.mataPelajaran', 'sumberCatatan.catatanPendampingan.pembuat',
            'laporanPerkembangan' => fn ($q) => $q->whereIn('id_laporan', $this->reports()->select('id_laporan'))->orderByDesc('tanggal_laporan'),
            'laporanPerkembangan.publikator']);
    }

    public function sources(RekapPerkembangan $recap): Collection
    {
        return $recap->sumberCatatan->map(function ($link) {
            $note = $link->catatanPerkembangan ?? $link->catatanPendampingan;
            $mapel = $link->catatanPerkembangan?->mataPelajaran?->nama_mapel;

            return ['link' => $link->getKey(), 'date' => $note->tanggal_catatan->format('Y-m-d'),
                'source' => $link->catatanPerkembangan ? 'Guru Mata Pelajaran' : 'Tim Inklusi',
                'teacher' => $note->pembuat->nama, 'subject' => $mapel,
                'event' => $link->catatanPerkembangan ? $note->kendala_peristiwa : $note->kegiatan_pendampingan,
                'action' => $link->catatanPerkembangan ? $note->tindakan : $note->tindak_lanjut,
                'result' => $link->catatanPerkembangan ? $note->hasil_tindakan : $note->kendala,
                'material' => $link->catatanPerkembangan?->materi_kegiatan,
                'extra' => $link->catatanPerkembangan?->catatan_tambahan];
        })->sortByDesc('date')->values();
    }

    public function document(RekapPerkembangan $recap, ?LaporanPerkembangan $report = null): array
    {
        return ['id' => $recap->getKey(), 'student' => $recap->siswa->nama_siswa, 'nis' => $recap->siswa->nis,
            'class' => $recap->siswa->kelas->nama_kelas, 'period' => $report?->periode ?? $recap->periode,
            'summary' => $recap->ringkasan_perkembangan, 'report' => $report?->isi_laporan ?? $recap->laporanPerkembangan->map(fn ($r) => $r->periode.': '.$r->isi_laporan)->implode("\n\n"),
            'author' => $recap->pembuat->nama, 'published' => ($report ?? $recap->laporanPerkembangan->first())?->tanggal_publikasi?->format('d-m-Y H:i'),
            'sources' => $this->sources($recap)->all()];
    }
}
