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
        abort_unless(app()->environment(['local', 'testing'])
            && $guru->status_akun === 'Aktif' && $guru->role?->nama_role === 'Guru Mapel', 403);

        return $guru;
    }

    public function assignments(Pengguna $guru): Builder
    {
        $this->actor($guru);

        return RelasiGuruMapelKelas::where('id_pengguna', $guru->getKey());
    }

    public function activeAssignmentsForStudent(Pengguna $guru, Siswa $siswa): Builder
    {
        return $this->assignments($guru)
            ->where('id_kelas', $siswa->id_kelas)
            ->whereHas('kelas', fn ($query) => $query
                ->when(Schema::hasColumn('kelas', 'status_kelas'), fn ($query) => $query->where('status_kelas', 'Aktif')))
            ->whereHas('mataPelajaran', fn ($query) => $query
                ->when(Schema::hasColumn('mata_pelajaran', 'status_mapel'), fn ($query) => $query->where('status_mapel', 'Aktif')));
    }

    public function assignedSubjectId(Pengguna $guru, Siswa $siswa): int
    {
        $subjectIds = $this->activeAssignmentsForStudent($guru, $siswa)
            ->distinct()->pluck('id_mapel');

        abort_unless($siswa->status_siswa === 'Aktif' && $subjectIds->isNotEmpty(), 403,
            'Mata pelajaran belum ditugaskan Admin untuk guru dan kelas siswa ini.');
        abort_unless($subjectIds->count() === 1, 409,
            'Terdapat lebih dari satu mata pelajaran aktif untuk guru dan kelas siswa ini. Hubungi Admin untuk memperbaiki penugasan.');

        return (int) $subjectIds->first();
    }

    public function students(Pengguna $guru): Builder
    {
        // No implicit ABK/non-null rule: display assigned students, pending product definition.
        return Siswa::query()->whereIn('id_kelas', $this->assignments($guru)->select('id_kelas'));
    }

    public function student(Pengguna $guru, int $id): Siswa
    {
        return $this->students($guru)->with(['kelas', 'relasiPendampingSiswa.pendamping.role'])->findOrFail($id);
    }

    public function notes(Pengguna $guru): Builder
    {
        return $this->roleValidatedNotes(CatatanPerkembangan::query())->where('id_pembuat', $this->actor($guru)->getKey())
            ->whereIn('id_siswa', $this->students($guru)->select('id_siswa'))
            ->whereHas('siswa', fn ($s) => $s->whereExists(function ($q) use ($guru) {
                $q->selectRaw('1')->from('relasi_guru_mapel_kelas as assignment')
                    ->where('assignment.id_pengguna', $guru->getKey())
                    ->whereColumn('assignment.id_kelas', 'siswa.id_kelas')
                    ->whereColumn('assignment.id_mapel', 'catatan_perkembangan.id_mapel');
            }));
    }

    public function roleValidatedNotes(Builder $query): Builder
    {
        $authorRole = $query->getModel() instanceof CatatanPerkembangan ? 'Guru Mapel' : 'Tim Inklusi';

        return $query->whereHas('pembuat.role', fn ($role) => $role->where('nama_role', $authorRole))
            ->whereDoesntHave('verifikasi.verifikator.role', fn ($role) => $role->where('nama_role', '!=', 'GPK'))
            ->whereDoesntHave('verifikasi.publikator.role', fn ($role) => $role->where('nama_role', '!=', 'GPK'));
    }

    public function writable(Pengguna $guru, Siswa $siswa, int $mapel): void
    {
        abort_unless($siswa->status_siswa === 'Aktif'
            && $this->activeAssignmentsForStudent($guru, $siswa)->where('id_mapel', $mapel)->exists(), 403);
    }
}
