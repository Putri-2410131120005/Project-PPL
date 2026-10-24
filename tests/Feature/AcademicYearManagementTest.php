<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manages_one_active_academic_year_and_classes_select_the_master(): void
    {
        $this->asAdmin()->post(route('dev.admin.academic-years.store'), $this->yearData('2026/2027'))
            ->assertRedirect(route('dev.admin.academic-years.index'));
        $this->post(route('dev.admin.academic-years.store'), $this->yearData('2027/2028'))
            ->assertRedirect(route('dev.admin.academic-years.index'));

        $first = TahunAjaran::query()->where('nama', '2026/2027')->firstOrFail();
        $second = TahunAjaran::query()->where('nama', '2027/2028')->firstOrFail();
        $this->patch(route('dev.admin.academic-years.activate', $first))->assertRedirect();
        $this->assertSame('Aktif', $first->fresh()->status);
        $this->patch(route('dev.admin.academic-years.activate', $second))->assertRedirect();
        $this->assertSame('Selesai', $first->fresh()->status);
        $this->assertSame('Aktif', $second->fresh()->status);
        $this->assertSame(1, TahunAjaran::query()->where('status', 'Aktif')->count());

        $this->post(route('dev.admin.classes.store'), [
            'nama_kelas' => 'X-A', 'tingkat' => 'X',
            'id_tahun_ajaran' => $second->getKey(), 'status_kelas' => 'Aktif',
        ])->assertRedirect(route('dev.admin.classes.index'));
        $this->assertDatabaseHas('kelas', [
            'nama_kelas' => 'X-A', 'id_tahun_ajaran' => $second->getKey(), 'tahun_ajaran' => '2027/2028',
        ]);
        $this->get(route('dev.admin.classes.create'))->assertOk()
            ->assertSee('id="id_tahun_ajaran"', false)->assertDontSee('name="tahun_ajaran"', false);
    }

    public function test_changing_student_class_preserves_class_history(): void
    {
        $year = TahunAjaran::query()->create($this->yearData('2026/2027') + ['status' => 'Aktif']);
        $firstClass = Kelas::query()->create([
            'nama_kelas' => 'X-A', 'tingkat' => 'X', 'id_tahun_ajaran' => $year->getKey(), 'status_kelas' => 'Aktif',
        ]);
        $secondClass = Kelas::query()->create([
            'nama_kelas' => 'XI-A', 'tingkat' => 'XI', 'id_tahun_ajaran' => $year->getKey(), 'status_kelas' => 'Aktif',
        ]);

        $this->asAdmin()->post(route('dev.admin.students.store'), [
            'id_kelas' => $firstClass->getKey(), 'nis' => 'TA-001', 'nama_siswa' => 'Siswa Tahun Ajaran',
            'kebutuhan_khusus' => 'Hambatan belajar', 'status_siswa' => 'Aktif',
        ])->assertRedirect(route('dev.admin.students.index'));
        $student = Siswa::query()->where('nis', 'TA-001')->firstOrFail();
        $this->assertDatabaseHas('riwayat_kelas_siswa', [
            'id_siswa' => $student->getKey(), 'id_kelas' => $firstClass->getKey(), 'status' => 'Aktif',
        ]);

        $this->put(route('dev.admin.students.update', $student), [
            'id_kelas' => $secondClass->getKey(), 'nis' => $student->nis, 'nama_siswa' => $student->nama_siswa,
            'kebutuhan_khusus' => $student->kebutuhan_khusus, 'status_siswa' => 'Aktif',
        ])->assertRedirect(route('dev.admin.students.index'));
        $this->assertDatabaseHas('riwayat_kelas_siswa', [
            'id_siswa' => $student->getKey(), 'id_kelas' => $firstClass->getKey(), 'status' => 'Selesai',
        ]);
        $this->assertDatabaseHas('riwayat_kelas_siswa', [
            'id_siswa' => $student->getKey(), 'id_kelas' => $secondClass->getKey(), 'status' => 'Aktif',
        ]);
    }

    private function asAdmin(): static
    {
        return $this->withSession(['dev_role' => 'admin']);
    }

    private function yearData(string $name): array
    {
        [$start, $end] = array_map('intval', explode('/', $name));

        return [
            'nama' => $name,
            'mulai_ganjil' => "{$start}-07-01",
            'selesai_ganjil' => "{$start}-12-31",
            'mulai_genap' => "{$end}-01-01",
            'selesai_genap' => "{$end}-06-30",
        ];
    }
}
