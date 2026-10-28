<?php

namespace Tests\Feature;

use App\Models\CatatanPendampingan;
use App\Models\CatatanPerkembangan;
use App\Models\Kelas;
use App\Models\LaporanPerkembangan;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RekapPerkembangan;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SumberRekapCatatan;
use App\Models\VerifikasiCatatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_preview_without_parent_identity_does_not_expose_students(): void
    {
        [$parent, $student] = $this->family('Wali Aman', 'Anak Rahasia', 'PT-001');

        $this->withSession(['dev_role' => 'orang-tua'])
            ->get(route('dev.preview', 'orang-tua'))
            ->assertOk()
            ->assertDontSee($student->nama_siswa);

        $this->get(route('dev.parent.dashboard'))->assertRedirect(route('dev.parent.login'));
    }

    public function test_parent_who_must_change_password_cannot_bypass_that_flow(): void
    {
        [$parent] = $this->family('Wali Password', 'Anak Password', 'PT-002');
        $parent->forceFill(['wajib_ganti_password' => true])->save();

        $this->actingAs($parent, 'parent')
            ->get(route('dev.parent.dashboard'))
            ->assertRedirect(route('dev.parent.password.edit'));
    }

    public function test_dashboard_only_contains_linked_child_and_verified_notes(): void
    {
        [$parentA, $studentA] = $this->family('Wali A', 'Anak A', 'PT-101');
        [, $studentB] = $this->family('Wali B', 'Anak B', 'PT-102');
        $published = $this->publishedAssistance($studentA, 'Catatan yang boleh dilihat');
        $this->unpublishedAssistance($studentA, 'Catatan rahasia belum terbit');
        $this->verifiedButUnpublishedAssistance($studentA, 'Catatan terverifikasi langsung terlihat');
        $oldVersion = $this->publishedAssistance($studentA, 'Publikasi versi lama');
        $oldVersion->forceFill(['versi_pengajuan' => 2, 'status_pengajuan' => 'Dikirim'])->save();
        $this->publishedAssistance($studentB, 'Catatan anak lain');

        $this->actingAs($parentA, 'parent')->get(route('dev.parent.dashboard'))
            ->assertOk()
            ->assertSee($studentA->nama_siswa)
            ->assertDontSee($studentB->nama_siswa)
            ->assertSee('Catatan yang boleh dilihat')
            ->assertDontSee('Catatan rahasia belum terbit')
            ->assertSee('Catatan terverifikasi langsung terlihat')
            ->assertDontSee('Publikasi versi lama')
            ->assertSee(route('dev.parent.notes.show', ['pendampingan', $published->getKey()]), false);
    }

    public function test_parent_cannot_open_another_child_note_or_unpublished_note(): void
    {
        [$parentA, $studentA] = $this->family('Wali A', 'Anak A', 'PT-201');
        [, $studentB] = $this->family('Wali B', 'Anak B', 'PT-202');
        $ownDraft = $this->unpublishedAssistance($studentA, 'Draft milik anak A');
        $otherPublished = $this->publishedAssistance($studentB, 'Terbit milik anak B');

        $this->actingAs($parentA, 'parent')
            ->get(route('dev.parent.notes.show', ['pendampingan', $ownDraft->getKey()]))
            ->assertNotFound();
        $this->get(route('dev.parent.notes.show', ['pendampingan', $otherPublished->getKey()]))
            ->assertNotFound();
    }

    public function test_notes_show_correct_source_filter_date_and_paginate(): void
    {
        [$parent, $student] = $this->family('Wali Filter', 'Anak Filter', 'PT-301');
        $target = $this->publishedDevelopment($student, 'Matematika', now()->subDay(), 'Hasil belajar terbit');
        for ($index = 0; $index < 10; $index++) {
            $this->publishedAssistance($student, 'Catatan terbit '.$index, now()->subDays($index + 2));
        }

        $this->actingAs($parent, 'parent')->get(route('dev.parent.notes.index'))
            ->assertOk()
            ->assertSee('Menampilkan <strong>1–10</strong>', false)
            ->assertSee('Guru Mapel — Matematika');

        $this->get(route('dev.parent.notes.index', ['tanggal' => $target->tanggal_catatan->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('value="'.$target->tanggal_catatan->format('Y-m-d').'"', false)
            ->assertSee('Guru Mapel — Matematika')
            ->assertSee('Menampilkan <strong>1–1</strong>', false);

        $this->get(route('dev.parent.notes.show', ['perkembangan', $target->getKey()]))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Guru Mata Pelajaran')
            ->assertDontSee('Ubah')
            ->assertDontSee('Hapus');
    }

    public function test_only_published_available_report_is_visible_and_empty_semester_is_clear(): void
    {
        [$parent, $student] = $this->family('Wali Laporan', 'Anak Laporan', 'PT-401');
        $note = $this->publishedAssistance($student, 'Ringkasan sumber laporan');
        $gpk = $this->user('GPK Laporan', 'GPK');
        $rekap = RekapPerkembangan::query()->create([
            'id_siswa' => $student->getKey(), 'id_pembuat' => $gpk->getKey(),
            'periode' => 'Ganjil 2026/2027', 'ringkasan_perkembangan' => 'Rekap semester', 'tanggal_rekap' => now(),
        ]);
        SumberRekapCatatan::query()->create(['id_rekap' => $rekap->getKey(), 'id_catatan_pendampingan' => $note->getKey()]);
        LaporanPerkembangan::query()->forceCreate([
            'id_rekap' => $rekap->getKey(), 'id_pembuat' => $gpk->getKey(), 'periode' => 'Ganjil 2026/2027',
            'isi_laporan' => 'Isi laporan yang dipublikasikan', 'status_laporan' => 'Tersedia',
            'tanggal_laporan' => now(), 'id_publikator' => $gpk->getKey(), 'tanggal_publikasi' => now(),
        ]);
        LaporanPerkembangan::query()->forceCreate([
            'id_rekap' => $rekap->getKey(), 'id_pembuat' => $gpk->getKey(), 'periode' => 'Genap 2026/2027',
            'isi_laporan' => 'Draft laporan tidak boleh terlihat', 'status_laporan' => 'Draft', 'tanggal_laporan' => now(),
        ]);

        $this->actingAs($parent, 'parent')->get(route('dev.parent.reports.index', ['semester' => 'Ganjil']))
            ->assertOk()
            ->assertSee('Isi laporan yang dipublikasikan')
            ->assertSee('Ringkasan sumber laporan')
            ->assertDontSee('Draft laporan tidak boleh terlihat')
            ->assertDontSee('PDF');

        $this->get(route('dev.parent.reports.index', ['semester' => 'Genap']))
            ->assertOk()
            ->assertSee('Belum ada laporan untuk semester ini')
            ->assertDontSee('Draft laporan tidak boleh terlihat');
    }

    private function family(string $parentName, string $studentName, string $nis): array
    {
        $parent = $this->user($parentName, 'Orang Tua');
        $class = Kelas::query()->firstOrCreate(
            ['nama_kelas' => 'XI Portal', 'tahun_ajaran' => '2026/2027'],
            ['tingkat' => 'XI', 'status_kelas' => 'Aktif']
        );
        $student = Siswa::query()->create([
            'id_kelas' => $class->getKey(), 'nis' => $nis, 'nama_siswa' => $studentName,
            'kebutuhan_khusus' => 'Hambatan belajar', 'status_siswa' => 'Aktif',
        ]);
        $parentData = OrangTua::query()->create(['id_pengguna' => $parent->getKey()]);
        RelasiOrangTuaSiswa::query()->create(['id_orang_tua' => $parentData->getKey(), 'id_siswa' => $student->getKey(), 'hubungan' => 'Wali']);

        return [$parent, $student];
    }

    private function publishedAssistance(Siswa $student, string $content, $date = null): CatatanPendampingan
    {
        $author = $this->user('Pendamping '.Str::random(5), 'Tim Inklusi');
        $note = CatatanPendampingan::query()->forceCreate([
            'id_siswa' => $student->getKey(), 'id_pembuat' => $author->getKey(),
            'tanggal_catatan' => ($date ?: now())->toDateString(), 'kegiatan_pendampingan' => $content,
            'tindak_lanjut' => 'Lanjutkan dukungan bertahap', 'status_pengajuan' => 'Terverifikasi',
            'versi_pengajuan' => 1, 'tanggal_kirim' => now(), 'idempotency_key' => (string) Str::uuid(),
        ]);
        $this->publication($note->getKey(), null);

        return $note;
    }

    private function unpublishedAssistance(Siswa $student, string $content): CatatanPendampingan
    {
        $author = $this->user('Pendamping Draft '.Str::random(5), 'Tim Inklusi');

        return CatatanPendampingan::query()->forceCreate([
            'id_siswa' => $student->getKey(), 'id_pembuat' => $author->getKey(),
            'tanggal_catatan' => now()->toDateString(), 'kegiatan_pendampingan' => $content,
            'status_pengajuan' => 'Dikirim', 'versi_pengajuan' => 1, 'tanggal_kirim' => now(),
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    private function verifiedButUnpublishedAssistance(Siswa $student, string $content): CatatanPendampingan
    {
        $author = $this->user('Pendamping Terverifikasi '.Str::random(5), 'Tim Inklusi');
        $gpk = $this->user('GPK Verifikasi '.Str::random(5), 'GPK');
        $note = CatatanPendampingan::query()->forceCreate([
            'id_siswa' => $student->getKey(), 'id_pembuat' => $author->getKey(),
            'tanggal_catatan' => now()->toDateString(), 'kegiatan_pendampingan' => $content,
            'status_pengajuan' => 'Terverifikasi', 'versi_pengajuan' => 1, 'tanggal_kirim' => now(),
            'idempotency_key' => (string) Str::uuid(),
        ]);
        VerifikasiCatatan::query()->forceCreate([
            'id_catatan_pendampingan' => $note->getKey(), 'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1, 'status_verifikasi' => 'Terverifikasi', 'tanggal_verifikasi' => now(),
        ]);

        return $note;
    }

    private function publishedDevelopment(Siswa $student, string $subjectName, $date, string $result): CatatanPerkembangan
    {
        $author = $this->user('Guru '.Str::random(5), 'Guru Mapel');
        $subject = MataPelajaran::query()->create(['nama_mapel' => $subjectName, 'status_mapel' => 'Aktif']);
        $note = CatatanPerkembangan::query()->forceCreate([
            'id_siswa' => $student->getKey(), 'id_pembuat' => $author->getKey(), 'id_mapel' => $subject->getKey(),
            'tanggal_catatan' => $date->toDateString(), 'materi_kegiatan' => 'Materi pembelajaran',
            'tindakan' => 'Latihan bertahap', 'hasil_tindakan' => $result,
            'status_pengajuan' => 'Terverifikasi', 'versi_pengajuan' => 1, 'tanggal_kirim' => now(),
        ]);
        $this->publication(null, $note->getKey());

        return $note;
    }

    private function publication(?int $assistanceId, ?int $developmentId): void
    {
        $gpk = $this->user('GPK '.Str::random(5), 'GPK');
        VerifikasiCatatan::query()->forceCreate([
            'id_catatan_pendampingan' => $assistanceId,
            'id_catatan_perkembangan' => $developmentId,
            'id_verifikator' => $gpk->getKey(), 'versi_pengajuan' => 1,
            'status_verifikasi' => 'Terverifikasi', 'tanggal_verifikasi' => now(),
            'id_publikator' => $gpk->getKey(), 'tanggal_publikasi' => now(),
        ]);
    }

    private function user(string $name, string $roleName): Pengguna
    {
        $role = Role::query()->firstOrCreate(['nama_role' => $roleName]);

        return Pengguna::query()->create([
            'id_role' => $role->getKey(), 'nama' => $name,
            'username' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'email' => Str::uuid().'@example.test', 'password' => 'password',
            'wajib_ganti_password' => false, 'status_akun' => 'Aktif',
        ]);
    }
}
