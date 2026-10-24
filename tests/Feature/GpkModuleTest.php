<?php

namespace Tests\Feature;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\Kelas;
use App\Models\LaporanBulanan;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\RelasiPendampingSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\VerifikasiCatatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class GpkModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_login_and_middleware_require_an_active_gpk_identity(): void
    {
        $gpk = $this->user('GPK Aktif', 'GPK');
        $nonGpk = $this->user('Bukan GPK', 'Tim Inklusi');
        $inactive = $this->user('GPK Nonaktif', 'GPK', 'Nonaktif');

        $this->post(route('dev.login.store'), ['role' => 'gpk', 'gpk_id' => $gpk->getKey()])
            ->assertRedirect(route('dev.preview', 'gpk'))
            ->assertSessionHas('dev_pengguna_id', $gpk->getKey());
        $this->get(route('dev.preview', 'gpk'))->assertRedirect(route('dev.gpk.dashboard'));
        $this->get(route('dev.gpk.dashboard'))->assertOk()->assertSee($gpk->nama)
            ->assertSee('Verifikasi Catatan')->assertSee('Riwayat Verifikasi');

        foreach ([$nonGpk, $inactive] as $invalid) {
            $this->from(route('dev.login'))->post(route('dev.login.store'), [
                'role' => 'gpk', 'gpk_id' => $invalid->getKey(),
            ])->assertRedirect(route('dev.login'))->assertSessionHasErrors('gpk_id');
        }

        $this->withSession(['dev_role' => 'gpk', 'dev_pengguna_id' => $nonGpk->getKey()])
            ->get(route('dev.gpk.dashboard'))->assertRedirect(route('dev.login'))
            ->assertSessionMissing(['dev_role', 'dev_pengguna_id']);
        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.gpk.dashboard'))->assertRedirect(route('dev.preview', 'admin'));
    }

    public function test_gpk_only_sees_assigned_students_and_read_only_parent_data(): void
    {
        [$gpk, $assigned, $outside] = $this->fixture();
        $parentUser = $this->user('Wali Siswa', 'Orang Tua');
        $parent = OrangTua::create(['id_pengguna' => $parentUser->getKey(), 'no_telepon' => '08123456789', 'alamat' => 'Banjarmasin']);
        RelasiOrangTuaSiswa::create(['id_orang_tua' => $parent->getKey(), 'id_siswa' => $assigned->getKey(), 'hubungan' => 'Ibu']);
        $outsideParentUser = $this->user('Wali Di Luar', 'Orang Tua');
        $outsideParent = OrangTua::create(['id_pengguna' => $outsideParentUser->getKey(), 'no_telepon' => '08000000000']);
        RelasiOrangTuaSiswa::create(['id_orang_tua' => $outsideParent->getKey(), 'id_siswa' => $outside->getKey(), 'hubungan' => 'Ayah']);

        $this->asGpk($gpk)->get(route('dev.gpk.students.index'))
            ->assertOk()->assertSee($assigned->nama_siswa)->assertDontSee($outside->nama_siswa);
        $this->get(route('dev.gpk.students.show', $outside))->assertNotFound();
        $this->get(route('dev.gpk.parents.index'))
            ->assertOk()->assertSee('Wali Siswa')->assertDontSee('Wali Di Luar')
            ->assertDontSee('Reset Password')->assertDontSee('Tambah Akun');
    }

    public function test_queue_excludes_drafts_decided_versions_and_invalid_source_roles(): void
    {
        [$gpk, $assigned] = $this->fixture();
        $guru = $this->user('Maya Guru', 'Guru Mapel');
        $pendamping = $this->user('Rina Pendamping', 'Tim Inklusi');
        $mapel = MataPelajaran::create(['nama_mapel' => 'Matematika']);
        $draft = $this->developmentNote($assigned, $guru, $mapel, 'Draft');
        $pending = $this->developmentNote($assigned, $guru, $mapel, 'Dikirim');
        $assistance = $this->assistanceNote($assigned, $pendamping, 'Dikirim');
        $wrongRole = $this->developmentNote($assigned, $pendamping, $mapel, 'Dikirim');
        $decided = $this->developmentNote($assigned, $guru, $mapel, 'Dikirim');
        VerifikasiCatatan::forceCreate([
            'id_catatan_perkembangan' => $decided->getKey(), 'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1, 'status_verifikasi' => 'Terverifikasi', 'tanggal_verifikasi' => now(),
        ]);

        $response = $this->asGpk($gpk)->get(route('dev.gpk.dashboard'));
        $response->assertOk()->assertSee(route('dev.gpk.notes.show', ['type' => 'perkembangan', 'id' => $pending->getKey()]), false)->assertSee('Maya Guru')
            ->assertSee('Matematika')->assertSee('Rina Pendamping')
            ->assertDontSee(route('dev.gpk.notes.show', ['type' => 'perkembangan', 'id' => $draft->getKey()]), false)
            ->assertDontSee(route('dev.gpk.notes.show', ['type' => 'perkembangan', 'id' => $wrongRole->getKey()]), false);
        $this->get(route('dev.gpk.notes.show', ['type' => 'perkembangan', 'id' => $draft->getKey()]))->assertNotFound();
    }

    public function test_gpk_can_review_and_verify_notes_for_students_outside_its_assignment(): void
    {
        [$gpk, $assigned, $outside] = $this->fixture();
        $guru = $this->user('Guru Siswa Luar', 'Guru Mapel');
        $pendamping = $this->user('Pendamping Siswa Luar', 'Tim Inklusi');
        $mapel = MataPelajaran::create(['nama_mapel' => 'IPS']);
        $development = $this->developmentNote($outside, $guru, $mapel, 'Dikirim');
        $assistance = $this->assistanceNote($outside, $pendamping, 'Dikirim');

        $this->asGpk($gpk)->get(route('dev.gpk.students.index'))
            ->assertOk()->assertSee($assigned->nama_siswa)->assertDontSee($outside->nama_siswa);

        $this->get(route('dev.gpk.notes.index'))
            ->assertOk()
            ->assertSee(route('dev.gpk.notes.show', ['type' => 'perkembangan', 'id' => $development->getKey()]), false)
            ->assertSee(route('dev.gpk.notes.show', ['type' => 'pendampingan', 'id' => $assistance->getKey()]), false);

        $this->get(route('dev.gpk.notes.show', ['type' => 'perkembangan', 'id' => $development->getKey()]))
            ->assertOk()->assertSee($outside->nama_siswa);
        $this->post(route('dev.gpk.notes.verify', ['type' => 'perkembangan', 'id' => $development->getKey()]), [
            'keputusan' => 'Terverifikasi',
        ])->assertRedirect();

        $this->assertSame('Terverifikasi', $development->fresh()->status_pengajuan);
        $this->get(route('dev.gpk.history.index'))
            ->assertOk()->assertSee($outside->nama_siswa)->assertSee('Guru Siswa Luar');
    }

    public function test_verification_immediately_publishes_revision_and_double_submit_use_service_rules(): void
    {
        [$gpk, $assigned] = $this->fixture();
        $guru = $this->user('Guru Sumber', 'Guru Mapel');
        $mapel = MataPelajaran::create(['nama_mapel' => 'Bahasa Indonesia']);
        $verified = $this->developmentNote($assigned, $guru, $mapel, 'Dikirim');

        $this->asGpk($gpk)->post(route('dev.gpk.notes.verify', ['type' => 'perkembangan', 'id' => $verified->getKey()]), [
            'keputusan' => 'Terverifikasi',
        ])->assertRedirect();
        $verified->refresh();
        $this->assertSame('Terverifikasi', $verified->status_pengajuan);
        $verification = VerifikasiCatatan::firstOrFail();
        $this->assertSame($gpk->getKey(), $verification->id_publikator);
        $this->assertNotNull($verification->tanggal_publikasi);
        $this->assertFalse(app('router')->has('dev.gpk.notes.publish'));

        $revision = $this->developmentNote($assigned, $guru, $mapel, 'Dikirim');
        $this->post(route('dev.gpk.notes.verify', ['type' => 'perkembangan', 'id' => $revision->getKey()]), [
            'keputusan' => 'Perlu Revisi', 'alasan' => '',
        ])->assertSessionHasErrors('alasan');
        $this->post(route('dev.gpk.notes.verify', ['type' => 'perkembangan', 'id' => $revision->getKey()]), [
            'keputusan' => 'Perlu Revisi', 'alasan' => 'Lengkapi hasil tindakan.',
        ])->assertRedirect();
        $this->assertDatabaseHas('verifikasi_catatan', [
            'id_catatan_perkembangan' => $revision->getKey(), 'status_verifikasi' => 'Perlu Revisi',
            'catatan_verifikasi' => 'Lengkapi hasil tindakan.',
        ]);
        $this->post(route('dev.gpk.notes.verify', ['type' => 'perkembangan', 'id' => $revision->getKey()]), [
            'keputusan' => 'Perlu Revisi', 'alasan' => 'Kirim lagi.',
        ])->assertSessionHasErrors('verifikasi');
        $this->assertDatabaseCount('verifikasi_catatan', 2);
    }

    public function test_history_keeps_old_versions_and_shows_safe_domain_errors(): void
    {
        [$gpk, $assigned] = $this->fixture();
        $pendamping = $this->user('Pendamping Riwayat', 'Tim Inklusi');
        $note = $this->assistanceNote($assigned, $pendamping, 'Dikirim');

        $this->asGpk($gpk)->post(route('dev.gpk.notes.verify', ['type' => 'pendampingan', 'id' => $note->getKey()]), [
            'keputusan' => 'Perlu Revisi', 'alasan' => 'Perjelas tindak lanjut.',
        ])->assertRedirect();
        $note->refresh()->forceFill(['status_pengajuan' => 'Dikirim', 'versi_pengajuan' => 2])->save();
        $this->asGpk($gpk)->post(route('dev.gpk.notes.verify', ['type' => 'pendampingan', 'id' => $note->getKey()]), [
            'keputusan' => 'Terverifikasi',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->get(route('dev.gpk.history.index'))->assertOk()
            ->assertSee('v1')->assertSee('v2')->assertSee('Perjelas tindak lanjut.')
            ->assertSee('Pendamping Riwayat');
        $this->assertDatabaseCount('verifikasi_catatan', 2);
    }

    public function test_semester_recap_is_read_only_and_covers_every_eligible_note_for_all_students(): void
    {
        $this->travelTo(Carbon::parse('2026-10-23 10:00:00'));
        [$gpk, $assigned, $outside] = $this->fixture();
        $headmaster = $this->user('Kepala Sekolah Rekap', 'Kepala Sekolah');
        $guru = $this->user('Guru Rekap', 'Guru Mapel');
        $pendamping = $this->user('Pendamping Rekap', 'Tim Inklusi');
        $mapel = MataPelajaran::create(['nama_mapel' => 'IPA']);
        $eligible = $this->developmentNote($outside, $guru, $mapel, 'Terverifikasi');
        VerifikasiCatatan::forceCreate([
            'id_catatan_perkembangan' => $eligible->getKey(), 'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1, 'status_verifikasi' => 'Terverifikasi', 'tanggal_verifikasi' => now(),
        ]);
        $assistance = $this->assistanceNote($outside, $pendamping, 'Terverifikasi');
        VerifikasiCatatan::forceCreate([
            'id_catatan_pendampingan' => $assistance->getKey(), 'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1, 'status_verifikasi' => 'Terverifikasi', 'tanggal_verifikasi' => now(),
        ]);
        $outsidePeriod = $this->developmentNote($outside, $guru, $mapel, 'Terverifikasi');
        $outsidePeriod->forceFill(['tanggal_catatan' => '2026-03-10'])->save();
        VerifikasiCatatan::forceCreate([
            'id_catatan_perkembangan' => $outsidePeriod->getKey(), 'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1, 'status_verifikasi' => 'Terverifikasi', 'tanggal_verifikasi' => now(),
        ]);
        $this->developmentNote($outside, $guru, $mapel, 'Draft');

        $this->asGpk($gpk)->get(route('dev.gpk.recaps.index', [
            'semester' => 'ganjil', 'tahun_ajaran' => '2026/2027',
        ]))->assertOk()->assertSee($assigned->nama_siswa)->assertSee($outside->nama_siswa)
            ->assertSee('2 catatan')->assertSee('Unduh ZIP');

        $otherClass = Kelas::create([
            'nama_kelas' => 'X IPA 1', 'tingkat' => 'X', 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif',
        ]);
        $excludedFromZip = Siswa::create([
            'id_kelas' => $otherClass->getKey(), 'nis' => 'GPK-003', 'nama_siswa' => 'Siswa Kelas Sepuluh',
            'kebutuhan_khusus' => 'Disleksia', 'status_siswa' => 'Aktif',
        ]);
        $zipResponse = $this->get(route('dev.gpk.recaps.zip.download', [
            'semester' => 'ganjil', 'tahun_ajaran' => '2026/2027',
            'cari' => 'nama-yang-tidak-ada', 'tingkat' => 'XI',
        ]));
        $zipResponse->assertOk()
            ->assertDownload('rekap-semua-siswa-kelas-xi-semester-ganjil-2026-2027.zip')
            ->assertHeader('content-type', 'application/zip');
        $zipPath = $zipResponse->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertSame(2, $zip->numFiles);
        $zipEntries = collect(range(0, $zip->numFiles - 1))->map(fn ($index) => $zip->getNameIndex($index));
        $this->assertTrue($zipEntries->contains(fn ($name) => Str::contains($name, Str::slug($assigned->nama_siswa))));
        $this->assertTrue($zipEntries->contains(fn ($name) => Str::contains($name, Str::slug($outside->nama_siswa))));
        $this->assertFalse($zipEntries->contains(fn ($name) => Str::contains($name, Str::slug($excludedFromZip->nama_siswa))));
        $zipEntries->each(fn ($name) => $this->assertStringStartsWith('%PDF', $zip->getFromName($name)));
        $zip->close();
        @unlink($zipPath);

        $this->get(route('dev.gpk.recaps.show', [
            'siswa' => $outside, 'semester' => 'ganjil', 'tahun_ajaran' => '2026/2027',
        ]))->assertOk()
            ->assertSee('Rekomendasi / Tindak Lanjut')
            ->assertSee('Tindakan bertahap.')
            ->assertSee('Tindak lanjut terukur.')
            ->assertSee('Ada perkembangan.')
            ->assertSee('Pendampingan', false)
            ->assertSee('Unduh Excel')
            ->assertDontSee('10 Mar 2026')
            ->assertDontSee('Ringkasan Perkembangan</label>', false)
            ->assertDontSee('Simpan Rekap Semester');

        $this->get(route('dev.gpk.recaps.export', [
            'siswa' => $outside, 'semester' => 'ganjil', 'tahun_ajaran' => '2026/2027',
        ]))->assertOk()->assertDownload();

        $this->get(route('dev.gpk.recaps.pdf.preview', [
            'siswa' => $outside, 'semester' => 'ganjil', 'tahun_ajaran' => '2026/2027',
        ]))->assertOk()->assertSee('Pratinjau PDF Laporan Perkembangan')
            ->assertSee($headmaster->nama)->assertSee($gpk->nama)->assertSee('Kepala Sekolah');
        $this->get(route('dev.gpk.recaps.pdf.download', [
            'siswa' => $outside, 'semester' => 'ganjil', 'tahun_ajaran' => '2026/2027',
        ]))->assertOk()->assertDownload()->assertHeader('content-type', 'application/pdf');

        $this->assertFalse(app('router')->has('dev.gpk.recaps.store'));
    }

    public function test_monthly_report_appears_at_month_end_and_only_needs_publication(): void
    {
        $this->travelTo(Carbon::parse('2026-10-23 10:00:00'));
        [$gpk, $assigned, $outside] = $this->fixture();
        $headmaster = $this->user('Kepala Sekolah Laporan', 'Kepala Sekolah');
        $guru = $this->user('Guru Laporan Bulanan', 'Guru Mapel');
        $mapel = MataPelajaran::create(['nama_mapel' => 'Bahasa Inggris']);
        $note = $this->developmentNote($outside, $guru, $mapel, 'Terverifikasi');
        VerifikasiCatatan::forceCreate([
            'id_catatan_perkembangan' => $note->getKey(), 'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1, 'status_verifikasi' => 'Terverifikasi', 'tanggal_verifikasi' => now(),
        ]);

        $this->asGpk($gpk)->get(route('dev.gpk.reports.index'))
            ->assertOk()->assertSee('Belum ada laporan bulanan');
        $this->assertDatabaseCount('laporan_bulanan', 0);

        $this->travelBack();
        $this->travelTo(Carbon::parse('2026-10-31 10:00:00'));
        $this->get(route('dev.gpk.reports.index'))
            ->assertOk()->assertSee('Oktober 2026')->assertSee('Belum Tersedia')->assertSee('Kelola Laporan');
        $report = LaporanBulanan::firstOrFail();
        $this->assertSame('Belum Tersedia', $report->status_laporan);
        $this->assertSame(2, $report->jumlah_siswa);
        $this->assertSame(1, $report->jumlah_catatan);
        $this->assertNull($report->id_publikator);

        $this->get(route('dev.gpk.reports.show', $report))
            ->assertOk()->assertSee($assigned->nama_siswa)->assertSee($outside->nama_siswa)
            ->assertSee('Ada perkembangan.')->assertSee('Publikasikan');
        $this->get(route('dev.gpk.reports.pdf.preview', $report))->assertOk()
            ->assertSee('Pratinjau PDF Laporan Perkembangan')->assertSee($headmaster->nama)->assertSee($gpk->nama);
        $this->get(route('dev.gpk.reports.pdf.download', $report))
            ->assertOk()->assertDownload()->assertHeader('content-type', 'application/pdf');

        $this->post(route('dev.gpk.reports.publish', $report))->assertRedirect();
        $report->refresh();
        $this->assertSame('Tersedia', $report->status_laporan);
        $this->assertSame($gpk->getKey(), $report->id_publikator);
        $this->assertNotNull($report->tanggal_publikasi);
        $this->post(route('dev.gpk.reports.publish', $report))->assertSessionHasErrors('publikasi');
        $this->assertFalse(app('router')->has('dev.gpk.reports.store'));
        $this->assertFalse(app('router')->has('dev.gpk.reports.update'));
    }

    public function test_empty_states_and_logout_do_not_create_dummy_data(): void
    {
        $gpk = $this->user('GPK Kosong', 'GPK');
        $this->asGpk($gpk)->get(route('dev.gpk.dashboard'))->assertOk()->assertSee('Tidak ada catatan');
        $this->get(route('dev.gpk.students.index'))->assertOk()->assertSee('Tidak ada data');
        $this->get(route('dev.gpk.recaps.index'))->assertOk()->assertSee('Tidak ada siswa');
        $this->get(route('dev.gpk.reports.index'))->assertOk()->assertSee('Belum ada laporan');
        $this->assertDatabaseCount('siswa', 0);
        $this->assertDatabaseCount('catatan_perkembangan', 0);
        $this->post(route('dev.logout'))->assertRedirect(route('dev.login'))
            ->assertSessionMissing(['dev_role', 'dev_pengguna_id']);
    }

    private function asGpk(Pengguna $gpk): static
    {
        return $this->withSession(['dev_role' => 'gpk', 'dev_pengguna_id' => $gpk->getKey()]);
    }

    private function fixture(): array
    {
        $gpk = $this->user('Nisa GPK', 'GPK');
        $class = Kelas::create(['nama_kelas' => 'XI IPA 1', 'tingkat' => 'XI', 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif']);
        $assigned = Siswa::create(['id_kelas' => $class->getKey(), 'nis' => 'GPK-001', 'nama_siswa' => 'Siswa Penugasan', 'kebutuhan_khusus' => 'Autisme', 'status_siswa' => 'Aktif']);
        $outside = Siswa::create(['id_kelas' => $class->getKey(), 'nis' => 'GPK-002', 'nama_siswa' => 'Siswa Luar', 'kebutuhan_khusus' => 'ADHD', 'status_siswa' => 'Aktif']);
        RelasiPendampingSiswa::create(['id_pengguna' => $gpk->getKey(), 'id_siswa' => $assigned->getKey()]);

        return [$gpk, $assigned, $outside];
    }

    private function user(string $name, string $roleName, string $status = 'Aktif'): Pengguna
    {
        $role = Role::firstOrCreate(['nama_role' => $roleName]);

        return Pengguna::create([
            'id_role' => $role->getKey(), 'nama' => $name,
            'username' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'email' => Str::uuid().'@example.test', 'password' => 'password', 'status_akun' => $status,
        ]);
    }

    private function developmentNote(Siswa $student, Pengguna $creator, MataPelajaran $subject, string $status): CatatanPerkembangan
    {
        return CatatanPerkembangan::forceCreate([
            'id_siswa' => $student->getKey(), 'id_pembuat' => $creator->getKey(), 'id_mapel' => $subject->getKey(),
            'tanggal_catatan' => now()->toDateString(), 'materi_kegiatan' => 'Materi '.Str::random(8),
            'kendala_peristiwa' => 'Pengamatan aktual.', 'tindakan' => 'Tindakan bertahap.',
            'hasil_tindakan' => 'Ada perkembangan.', 'status_pengajuan' => $status,
            'versi_pengajuan' => 1, 'tanggal_kirim' => $status === 'Draft' ? null : now(),
        ]);
    }

    private function assistanceNote(Siswa $student, Pengguna $creator, string $status): CatatanPendampingan
    {
        return CatatanPendampingan::forceCreate([
            'id_siswa' => $student->getKey(), 'id_pembuat' => $creator->getKey(),
            'tanggal_catatan' => now()->toDateString(), 'kegiatan_pendampingan' => 'Pendampingan '.Str::random(8),
            'kendala' => 'Kendala aktual.', 'tindak_lanjut' => 'Tindak lanjut terukur.',
            'status_pengajuan' => $status, 'versi_pengajuan' => 1,
            'tanggal_kirim' => $status === 'Draft' ? null : now(), 'idempotency_key' => (string) Str::uuid(),
        ]);
    }
}
