<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDataLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_can_be_archived_without_removing_students_or_history_links(): void
    {
        $class = Kelas::create([
            'nama_kelas' => 'X Arsip',
            'tingkat' => 'X',
            'tahun_ajaran' => '2025/2026',
            'status_kelas' => 'Aktif',
        ]);
        $student = Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => 'ARSIP-001',
            'nama_siswa' => 'Siswa Riwayat',
            'status_siswa' => 'Lulus',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->patch(route('dev.admin.classes.status', $class))
            ->assertRedirect(route('dev.admin.classes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kelas', [
            'id_kelas' => $class->id_kelas,
            'status_kelas' => 'Diarsipkan',
        ]);
        $this->assertDatabaseHas('siswa', ['id_siswa' => $student->id_siswa]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.students.create'))
            ->assertOk()
            ->assertDontSee('X Arsip');

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.mappings.guru-mapel-kelas.index'))
            ->assertOk()
            ->assertDontSee('X Arsip');
    }

    public function test_student_deletion_is_not_routed_and_statuses_remain_available(): void
    {
        $class = Kelas::create([
            'nama_kelas' => 'XI A',
            'tingkat' => 'XI',
            'tahun_ajaran' => '2026/2027',
        ]);
        $student = Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => 'SISWA-001',
            'nama_siswa' => 'Siswa Dipertahankan',
            'status_siswa' => 'Aktif',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.students.index'))
            ->assertOk()
            ->assertSee('Semua status')
            ->assertSeeInOrder(['Aktif', 'Lulus', 'Pindah', 'Nonaktif'])
            ->assertDontSee('Hapus data siswa secara permanen');

        $this->withSession(['dev_role' => 'admin'])
            ->delete("/_dev/admin/siswa/{$student->id_siswa}")
            ->assertStatus(405);

        $this->assertDatabaseHas('siswa', ['id_siswa' => $student->id_siswa]);
    }

    public function test_subject_can_be_archived_and_is_excluded_from_new_mappings(): void
    {
        $subject = MataPelajaran::create(['nama_mapel' => 'Mapel Lama']);

        $this->withSession(['dev_role' => 'admin'])
            ->patch(route('dev.admin.subjects.status', $subject))
            ->assertRedirect(route('dev.admin.subjects.index'));

        $this->assertDatabaseHas('mata_pelajaran', [
            'id_mapel' => $subject->id_mapel,
            'status_mapel' => 'Diarsipkan',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.mappings.guru-mapel-kelas.index'))
            ->assertOk()
            ->assertDontSee('value="'.$subject->id_mapel.'"', false);
    }

    public function test_subject_form_stores_its_related_classes(): void
    {
        $firstClass = Kelas::create([
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);
        $secondClass = Kelas::create([
            'nama_kelas' => 'X B',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.subjects.store'), [
                'nama_mapel' => 'Bahasa Indonesia',
                'status_mapel' => 'Aktif',
                'id_kelas' => [$firstClass->id_kelas, $secondClass->id_kelas],
            ])
            ->assertRedirect(route('dev.admin.subjects.index'));

        $subject = MataPelajaran::where('nama_mapel', 'Bahasa Indonesia')->firstOrFail();
        $this->assertDatabaseHas('relasi_kelas_mapel', [
            'id_mapel' => $subject->id_mapel,
            'id_kelas' => $firstClass->id_kelas,
        ]);
        $this->assertDatabaseHas('relasi_kelas_mapel', [
            'id_mapel' => $subject->id_mapel,
            'id_kelas' => $secondClass->id_kelas,
        ]);
    }

    public function test_students_can_be_sorted_by_name_in_both_directions(): void
    {
        $class = Kelas::create([
            'nama_kelas' => 'XI Urutan',
            'tingkat' => 'XI',
            'tahun_ajaran' => '2026/2027',
        ]);

        Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => 'URUT-002',
            'nama_siswa' => 'Zahra Uji Urutan',
            'status_siswa' => 'Aktif',
        ]);
        Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => 'URUT-001',
            'nama_siswa' => 'Andi Uji Urutan',
            'status_siswa' => 'Aktif',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.students.index', ['urut_nama' => 'az']))
            ->assertOk()
            ->assertSee('Nama Siswa')
            ->assertSee('A–Z ↑')
            ->assertSee('urut_nama=za', false)
            ->assertSeeInOrder(['Andi Uji Urutan', 'Zahra Uji Urutan']);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.students.index', ['urut_nama' => 'za']))
            ->assertOk()
            ->assertSee('Z–A ↓')
            ->assertSee('urut_nama=az', false)
            ->assertSeeInOrder(['Zahra Uji Urutan', 'Andi Uji Urutan']);
    }

    public function test_table_pagination_is_consistent_and_preserves_filters(): void
    {
        foreach (range(1, 12) as $number) {
            Kelas::create([
                'nama_kelas' => 'Kelas Pagination '.$number,
                'tingkat' => 'X',
                'tahun_ajaran' => '2026/2027',
                'status_kelas' => 'Aktif',
            ]);
        }

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.classes.index', [
                'status' => 'Aktif',
                'per_halaman' => 10,
            ]))
            ->assertOk()
            ->assertSee('Menampilkan <strong>1–10</strong>', false)
            ->assertSee('dari <strong>12</strong> data', false)
            ->assertSee('Baris per halaman')
            ->assertSee('name="status" value="Aktif"', false)
            ->assertSee('page=2', false)
            ->assertSee('aria-current="page">1</span>', false);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.classes.index', [
                'status' => 'Aktif',
                'per_halaman' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSee('Menampilkan <strong>11–12</strong>', false)
            ->assertSee('aria-current="page">2</span>', false);
    }
}
