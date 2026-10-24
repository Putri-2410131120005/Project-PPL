<?php

namespace Tests\Feature;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Services\GpkVerifikasiService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GpkVerifikasiServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $idGpk;
    private int $idGuru;
    private int $idPendamping;
    private int $idSiswa;
    private int $idMapel;

    protected function setUp(): void
    {
        parent::setUp();

        $roleGpk = DB::table('role')->insertGetId(['nama_role' => 'GPK'], 'id_role');
        $roleGuru = DB::table('role')->insertGetId(['nama_role' => 'Guru Mapel'], 'id_role');
        $rolePendamping = DB::table('role')->insertGetId(['nama_role' => 'Tim Inklusi'], 'id_role');
        $this->idGpk = DB::table('pengguna')->insertGetId([
            'id_role' => $roleGpk, 'nama' => 'GPK Uji', 'email' => 'gpk-uji@example.test',
            'password' => bcrypt('password-testing'), 'status_akun' => 'Aktif',
        ], 'id_pengguna');
        $this->idGuru = DB::table('pengguna')->insertGetId([
            'id_role' => $roleGuru, 'nama' => 'Guru Uji', 'email' => 'guru-uji@example.test',
            'password' => bcrypt('password-testing'), 'status_akun' => 'Aktif',
        ], 'id_pengguna');
        $this->idPendamping = DB::table('pengguna')->insertGetId([
            'id_role' => $rolePendamping, 'nama' => 'Pendamping Uji', 'email' => 'pendamping-uji@example.test',
            'password' => bcrypt('password-testing'), 'status_akun' => 'Aktif',
        ], 'id_pengguna');
        $idKelas = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'X-A', 'tingkat' => 'X', 'tahun_ajaran' => '2026/2027',
        ], 'id_kelas');
        $this->idSiswa = DB::table('siswa')->insertGetId([
            'id_kelas' => $idKelas, 'nis' => 'UJI-001', 'nama_siswa' => 'Siswa Dummy',
        ], 'id_siswa');
        $this->idMapel = DB::table('mata_pelajaran')->insertGetId([
            'nama_mapel' => 'Matematika',
        ], 'id_mapel');
    }

    private function perkembangan(): int
    {
        return DB::table('catatan_perkembangan')->insertGetId([
            'id_siswa' => $this->idSiswa, 'id_pembuat' => $this->idGuru,
            'id_mapel' => $this->idMapel, 'tanggal_catatan' => '2026-10-16',
            'materi_kegiatan' => 'Materi dummy', 'status_pengajuan' => 'Dikirim',
            'versi_pengajuan' => 1,
        ], 'id_catatan_perkembangan');
    }

    private function pendampingan(): int
    {
        return DB::table('catatan_pendampingan')->insertGetId([
            'id_siswa' => $this->idSiswa, 'id_pembuat' => $this->idPendamping,
            'tanggal_catatan' => '2026-10-16',
            'kegiatan_pendampingan' => 'Kegiatan dummy', 'status_pengajuan' => 'Dikirim',
            'versi_pengajuan' => 1,
        ], 'id_catatan_pendampingan');
    }

    public function test_verified_subject_note_is_immediately_available_to_parent(): void
    {
        $id = $this->perkembangan();
        $svc = app(GpkVerifikasiService::class);
        $verifikasi = $svc->verifikasi('perkembangan', $id, $this->idGpk, 'Terverifikasi');
        $this->assertEquals(1, CatatanPerkembangan::dipublikasikan()->count());
        $this->assertDatabaseHas('verifikasi_catatan', [
            'id_verifikasi' => $verifikasi->id_verifikasi, 'id_publikator' => $this->idGpk,
        ]);
        $this->assertNotNull($verifikasi->tanggal_publikasi);
    }

    public function test_pendamping_note_sent_back_for_revision_is_not_available_to_parent(): void
    {
        $id = $this->pendampingan();
        $svc = app(GpkVerifikasiService::class);
        $verifikasi = $svc->verifikasi('pendampingan', $id, $this->idGpk, 'Perlu Revisi', 'Lengkapi hasil.');
        $this->assertEquals(0, CatatanPendampingan::dipublikasikan()->count());
        $this->assertNull($verifikasi->id_publikator);
        $this->assertNull($verifikasi->tanggal_publikasi);
    }

    public function test_only_active_gpk_may_verify(): void
    {
        $id = $this->perkembangan();
        $svc = app(GpkVerifikasiService::class);
        $this->expectException(DomainException::class);
        $svc->verifikasi('perkembangan', $id, $this->idGuru, 'Terverifikasi');
    }

    public function test_old_verification_version_is_not_available_to_parent(): void
    {
        $id = $this->perkembangan();
        $svc = app(GpkVerifikasiService::class);
        $verifikasi = $svc->verifikasi('perkembangan', $id, $this->idGpk, 'Terverifikasi');
        $verifikasi->forceFill(['id_publikator' => null, 'tanggal_publikasi' => null])->save();
        $this->assertEquals(1, CatatanPerkembangan::dipublikasikan()->count());
        DB::table('catatan_perkembangan')->where('id_catatan_perkembangan', $id)
            ->update(['versi_pengajuan' => 2, 'status_pengajuan' => 'Dikirim']);
        $this->assertEquals(0, CatatanPerkembangan::dipublikasikan()->count());
    }

    public function test_same_version_cannot_be_verified_twice(): void
    {
        $id = $this->perkembangan();
        $svc = app(GpkVerifikasiService::class);
        $svc->verifikasi('perkembangan', $id, $this->idGpk, 'Terverifikasi');
        DB::table('catatan_perkembangan')->where('id_catatan_perkembangan', $id)
            ->update(['status_pengajuan' => 'Dikirim']); // simulate an attempted duplicate
        $this->expectException(DomainException::class);
        $svc->verifikasi('perkembangan', $id, $this->idGpk, 'Terverifikasi');
    }
}
