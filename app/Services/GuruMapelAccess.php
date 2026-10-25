<?php

namespace App\Services;

use App\Models\CatatanPerkembangan;
use App\Models\Pengguna;
use App\Models\RelasiGuruMapelKelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/** Domain access checks also apply when called outside an HTTP request. */
class GuruMapelAccess
{
    public function actor(Pengguna $guru): Pengguna
    {
        $guru = Pengguna::with('role')->findOrFail($guru->getKey());
        abort_unless(app()->environment(['local', 'testing']) && GuruMapelDemoIdentity::isAllowedActor($guru)
            && $guru->status_akun === 'Aktif' && $guru->role?->nama_role === 'Guru Mapel', 403);

        return $guru;
    }

    public function assignments(Pengguna $guru): Builder
    {
        $this->actor($guru);

        return RelasiGuruMapelKelas::where('id_pengguna', $guru->getKey())
            ->whereHas('kelas', fn ($q) => $q->where('nama_kelas', 'like', 'DATA DEMO %'))
            ->whereHas('mataPelajaran', fn ($q) => $q->where('nama_mapel', 'like', 'DATA DEMO %'));
    }

    public function students(Pengguna $guru): Builder
    {
        // No implicit ABK/non-null rule: display assigned students, pending product definition.
        return Siswa::where('nis', 'like', 'DEMO-A16-%')->where('nama_siswa', 'like', 'DATA DEMO %')
            ->whereIn('id_kelas', $this->assignments($guru)->select('id_kelas'));
    }

    public function student(Pengguna $guru, int $id): Siswa
    {
        return $this->students($guru)->with(['kelas', 'relasiPendampingSiswa' => fn ($q) => $q->whereHas('pendamping', fn ($p) => $p->where('nama', 'like', 'DATA DEMO %')->where('email', 'like', '%@example.test')), 'relasiPendampingSiswa.pendamping.role'])->findOrFail($id);
    }

    public function notes(Pengguna $guru): Builder
    {
        return $this->syntheticNotes(CatatanPerkembangan::query())->where('id_pembuat', $this->actor($guru)->getKey())
            ->whereHas('mataPelajaran', fn ($q) => $q->where('nama_mapel', 'like', 'DATA DEMO %'))
            ->whereIn('id_siswa', $this->students($guru)->select('id_siswa'))
            ->whereHas('siswa', fn ($s) => $s->whereExists(function ($q) use ($guru) {
                $q->selectRaw('1')->from('relasi_guru_mapel_kelas as assignment')
                    ->where('assignment.id_pengguna', $guru->getKey())
                    ->whereColumn('assignment.id_kelas', 'siswa.id_kelas')
                    ->whereColumn('assignment.id_mapel', 'catatan_perkembangan.id_mapel');
            }));
    }

    public function syntheticNotes(Builder $query): Builder
    {
        // Related authors/decisions must also be synthetic; never expose real staff through demo history.
        return $query->whereHas('pembuat', fn ($q) => $q->where('nama', 'like', 'DATA DEMO %')->where('email', 'like', '%@example.test'))
            ->whereDoesntHave('verifikasi.verifikator', fn ($q) => $q->where('nama', 'not like', 'DATA DEMO %')->orWhereNull('email')->orWhere('email', 'not like', '%@example.test'))
            ->whereDoesntHave('verifikasi.publikator', fn ($q) => $q->where('nama', 'not like', 'DATA DEMO %')->orWhereNull('email')->orWhere('email', 'not like', '%@example.test'));
    }

    public function writable(Pengguna $guru, Siswa $siswa, int $mapel): void
    {
        abort_unless($siswa->status_siswa === 'Aktif' && $this->assignments($guru)
            ->where('id_kelas', $siswa->id_kelas)->where('id_mapel', $mapel)
            ->whereHas('kelas', fn ($q) => $q->when(Schema::hasColumn('kelas', 'status_kelas'), fn ($q) => $q->where('status_kelas', 'Aktif')))
            ->whereHas('mataPelajaran', fn ($q) => $q->when(Schema::hasColumn('mata_pelajaran', 'status_mapel'), fn ($q) => $q->where('status_mapel', 'Aktif')))->exists(), 403);
    }
}
