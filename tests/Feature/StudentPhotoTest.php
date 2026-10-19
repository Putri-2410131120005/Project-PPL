<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\Role;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_a_student_photo(): void
    {
        Storage::fake('public');
        $class = $this->makeClass();
        $photo = UploadedFile::fake()->image('anak.jpg', 500, 500);

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.students.store'), [
                'id_kelas' => $class->id_kelas,
                'nis' => 'FOTO-001',
                'nama_siswa' => 'Anak Dengan Foto',
                'foto' => $photo,
                'kebutuhan_khusus' => 'Hambatan penglihatan',
                'status_siswa' => 'Aktif',
            ])
            ->assertRedirect(route('dev.admin.students.index'))
            ->assertSessionHasNoErrors();

        $student = Siswa::where('nis', 'FOTO-001')->firstOrFail();

        $this->assertNotNull($student->foto);
        Storage::disk('public')->assertExists($student->foto);
    }

    public function test_replacing_a_student_photo_removes_the_old_file(): void
    {
        Storage::fake('public');
        $class = $this->makeClass();
        $oldPath = UploadedFile::fake()->image('lama.jpg')->store('foto-siswa', 'public');
        $student = Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => 'FOTO-002',
            'nama_siswa' => 'Anak Ubah Foto',
            'foto' => $oldPath,
            'status_siswa' => 'Aktif',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->put(route('dev.admin.students.update', $student), [
                'id_kelas' => $class->id_kelas,
                'nis' => $student->nis,
                'nama_siswa' => $student->nama_siswa,
                'foto' => UploadedFile::fake()->image('baru.png'),
                'kebutuhan_khusus' => 'Hambatan penglihatan',
                'status_siswa' => 'Aktif',
            ])
            ->assertRedirect(route('dev.admin.students.index'))
            ->assertSessionHasNoErrors();

        $student->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($student->foto);
    }

    public function test_parent_dashboard_displays_the_linked_child_photo(): void
    {
        Storage::fake('public');
        $class = $this->makeClass();
        $photoPath = UploadedFile::fake()->image('anak.png')->store('foto-siswa', 'public');
        $student = Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => 'FOTO-003',
            'nama_siswa' => 'Anak Terhubung',
            'foto' => $photoPath,
            'status_siswa' => 'Aktif',
        ]);
        $parentRole = Role::create(['nama_role' => 'Orang Tua']);
        $account = Pengguna::create([
            'id_role' => $parentRole->id_role,
            'nama' => 'Wali Anak',
            'username' => $student->nis,
            'email' => 'wali-foto@example.test',
            'password' => 'password',
            'status_akun' => 'Aktif',
        ]);
        $parent = OrangTua::create(['id_pengguna' => $account->id_pengguna]);
        RelasiOrangTuaSiswa::create([
            'id_orang_tua' => $parent->id_orang_tua,
            'id_siswa' => $student->id_siswa,
            'hubungan' => 'Ibu',
        ]);

        $this->withSession(['dev_role' => 'orang-tua'])
            ->get(route('dev.preview', 'orang-tua'))
            ->assertOk()
            ->assertSee('Anak Terhubung')
            ->assertSee(Storage::disk('public')->url($photoPath));
    }

    private function makeClass(): Kelas
    {
        return Kelas::create([
            'nama_kelas' => 'X Foto',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);
    }
}
