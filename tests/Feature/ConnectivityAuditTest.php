<?php

namespace Tests\Feature;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\LaporanBulanan;
use App\Models\Pengguna;
use App\Models\Siswa;
use Database\Seeders\ConnectivityAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectivityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_role_is_connected_to_the_end_of_month_workflow(): void
    {
        $this->seed(ConnectivityAuditSeeder::class);

        $admin = Pengguna::query()->where('username', 'admin.integrasi')->sole();
        $principal = Pengguna::query()->where('username', 'kepala.integrasi')->sole();
        $gpk = Pengguna::query()->where('username', 'gpk.integrasi')->sole();
        $teacher = Pengguna::query()->where('username', 'guru.integrasi')->sole();
        $companion = Pengguna::query()->where('username', 'tim.integrasi')->sole();
        $parent = Pengguna::query()->where('username', 'ortu_AUDIT2601')->sole();
        $student = Siswa::query()->where('nis', 'AUDIT2601')->sole();

        $this->assertSame(3, Pengguna::query()->where('status_akun', 'Aktif')
            ->whereHas('role', fn ($roles) => $roles->where('nama_role', 'GPK'))->count());
        foreach (['Ketua', 'Sekretaris', 'Bendahara'] as $position) {
            $this->assertDatabaseHas('pengguna', ['jabatan_gpk' => $position, 'status_akun' => 'Aktif']);
        }

        $this->assertSame(3, CatatanPerkembangan::query()->where('id_pembuat', $teacher->getKey())->count());
        $this->assertSame(3, CatatanPendampingan::query()->where('id_pembuat', $companion->getKey())->count());
        $this->assertSame(2, CatatanPerkembangan::query()->where('id_pembuat', $teacher->getKey())->where('status_pengajuan', 'Terverifikasi')->count());
        $this->assertSame(1, CatatanPerkembangan::query()->where('id_pembuat', $teacher->getKey())->where('status_pengajuan', 'Perlu Revisi')->count());
        $this->assertDatabaseCount('verifikasi_catatan', 6);
        $this->assertDatabaseHas('verifikasi_catatan', ['status_verifikasi' => 'Terverifikasi', 'catatan_verifikasi' => null]);
        $this->assertDatabaseHas('verifikasi_catatan', [
            'status_verifikasi' => 'Terverifikasi',
            'catatan_verifikasi' => 'Pertahankan bantuan visual dan berikan waktu pengerjaan bertahap.',
        ]);
        $this->assertDatabaseHas('verifikasi_catatan', ['status_verifikasi' => 'Perlu Revisi']);
        $report = LaporanBulanan::query()->whereDate('periode_bulan', '2026-09-01')->sole();
        $this->assertSame('Belum Tersedia', $report->status_laporan);
        $this->assertNull($report->id_publikator);
        $this->assertNull($report->tanggal_publikasi);
        $this->assertSame(4, $report->jumlah_catatan);

        foreach (['users.index', 'staff.index', 'students.index', 'classes.index', 'subjects.index',
            'mappings.guru-mapel-kelas.index', 'mappings.pendamping-siswa.index', 'mappings.orang-tua-siswa.index'] as $route) {
            $this->withSession(['dev_role' => 'admin'])->get(route('dev.admin.'.$route))->assertOk();
        }

        $this->actingAs($teacher, 'guru_dev')->withSession([
            'dev_role' => 'guru-mapel', 'dev_pengguna_id' => $teacher->getKey(),
        ]);
        foreach (['dashboard', 'students.index', 'notes.status'] as $route) {
            $this->get(route('dev.guru-mapel.'.$route))->assertOk();
        }
        $this->get(route('dev.guru-mapel.students.show', $student))->assertOk()->assertSee('Nadia Audit Integrasi');

        $this->withSession(['dev_role' => 'tim-inklusi', 'dev_pengguna_id' => $companion->getKey()]);
        foreach (['dashboard', 'students.index', 'notes.index'] as $route) {
            $this->get(route('dev.tim-inklusi.'.$route))->assertOk();
        }
        $this->get(route('dev.tim-inklusi.students.show', $student))->assertOk()->assertSee('Nadia Audit Integrasi');

        $this->withSession(['dev_role' => 'gpk', 'dev_pengguna_id' => $gpk->getKey()]);
        foreach (['dashboard', 'students.index', 'notes.index', 'history.index', 'recaps.index', 'reports.index', 'parents.index'] as $route) {
            $this->get(route('dev.gpk.'.$route))->assertOk();
        }
        $this->get(route('dev.gpk.reports.show', $report))->assertOk()->assertSee('September 2026');
        $this->get(route('dev.gpk.reports.show', ['laporan' => $report, 'siswa' => $student]))
            ->assertOk()->assertSee('Ringkasan siswa')
            ->assertSee('Pertahankan bantuan visual dan berikan waktu pengerjaan bertahap.');

        $this->withSession(['dev_role' => 'kepala-sekolah', 'dev_pengguna_id' => $principal->getKey()]);
        foreach (['dashboard', 'students', 'recaps', 'archives'] as $route) {
            $this->get(route('dev.principal.'.$route))->assertOk();
        }
        $this->get(route('dev.principal.archives'))->assertDontSee('September 2026');
        $this->get(route('dev.principal.period', ['periode' => 'September 2026']))
            ->assertNotFound();

        $parent->forceFill(['wajib_ganti_password' => false, 'password_diubah_pada' => now()])->save();
        $this->actingAs($parent, 'parent')->withSession(['dev_role' => 'orang-tua']);
        foreach (['dashboard', 'notes.index'] as $route) {
            $this->get(route('dev.parent.'.$route))->assertOk();
        }
        $this->get(route('dev.parent.reports.index', ['semester' => 'Ganjil']))
            ->assertOk()->assertDontSee('September 2026');

        $this->assertSame('Admin', $admin->role->nama_role);
    }
}
