<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\OrangTua;
use App\Models\Pengguna;
use App\Models\RelasiOrangTuaSiswa;
use App\Models\Role;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $gpkRole;

    private Role $parentRole;

    private Role $kepalaSekolahRole;

    private Role $guruMapelRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['nama_role' => 'Admin / Pengelola']);
        $this->gpkRole = Role::create(['nama_role' => 'GPK']);
        $this->parentRole = Role::create(['nama_role' => 'Orang Tua']);
        $this->kepalaSekolahRole = Role::create(['nama_role' => 'Kepala Sekolah']);
        $this->guruMapelRole = Role::create(['nama_role' => 'Guru Mapel']);
    }

    public function test_user_management_requires_the_development_admin_role(): void
    {
        $this->get(route('dev.admin.users.index'))->assertRedirect(route('dev.login'));

        $this->withSession(['dev_role' => 'gpk'])
            ->get(route('dev.admin.users.index'))
            ->assertRedirect(route('dev.preview', 'gpk'));
    }

    public function test_admin_can_list_search_and_filter_users(): void
    {
        $nisa = $this->makeUser('Nisa Rahmawati', 'nisa@example.test', $this->gpkRole);
        $nisa->update(['username' => 'nisa-gpk']);
        $this->makeUser('Admin Sekolah', 'admin@example.test', $this->adminRole);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['cari' => 'Nisa']))
            ->assertOk()
            ->assertSee('Nisa Rahmawati')
            ->assertDontSee('admin@example.test');

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['role' => $this->adminRole->id_role]))
            ->assertOk()
            ->assertSee('Admin Sekolah')
            ->assertDontSee('nisa@example.test');

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['cari' => 'nisa-gpk']))
            ->assertOk()
            ->assertSee('Nisa Rahmawati');

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['cari' => '197001012020121001']))
            ->assertOk()
            ->assertSee('Nisa Rahmawati');
    }

    public function test_admin_can_create_update_and_toggle_a_user_status(): void
    {
        $payload = [
            'nama' => 'Pengguna Baru',
            'username' => 'pengguna-baru',
            'email' => 'baru@example.test',
            'id_role' => $this->gpkRole->id_role,
            'nip' => '198901012020121001',
            'status_akun' => 'Aktif',
        ];

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.users.store'), $payload)
            ->assertRedirect(route('dev.admin.users.index'));

        $this->assertDatabaseHas('pengguna', $payload);
        $user = Pengguna::where('email', $payload['email'])->firstOrFail();
        $this->assertTrue(Hash::check('not-the-user-password', $user->password) === false);

        $this->withSession(['dev_role' => 'admin'])
            ->put(route('dev.admin.users.update', $user), array_merge($payload, ['nama' => 'Pengguna Diperbarui']))
            ->assertRedirect(route('dev.admin.users.index'));

        $this->assertDatabaseHas('pengguna', ['id_pengguna' => $user->id_pengguna, 'nama' => 'Pengguna Diperbarui']);

        $this->withSession(['dev_role' => 'admin'])
            ->patch(route('dev.admin.users.status', $user))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengguna', ['id_pengguna' => $user->id_pengguna, 'status_akun' => 'Nonaktif']);
    }

    public function test_user_data_is_validated(): void
    {
        $this->withSession(['dev_role' => 'admin'])
            ->from(route('dev.admin.users.create'))
            ->post(route('dev.admin.users.store'), [])
            ->assertRedirect(route('dev.admin.users.create'))
            ->assertSessionHasErrors(['nama', 'email', 'id_role', 'status_akun']);
    }

    public function test_nip_is_required_for_educator_and_kepala_sekolah_roles(): void
    {
        $this->withSession(['dev_role' => 'admin'])
            ->from(route('dev.admin.users.create'))
            ->post(route('dev.admin.users.store'), [
                'nama' => 'GPK Tanpa NIP',
                'email' => 'gpk-tanpa-nip@example.test',
                'id_role' => $this->gpkRole->id_role,
                'status_akun' => 'Aktif',
            ])
            ->assertRedirect(route('dev.admin.users.create'))
            ->assertSessionHasErrors('nip');

        $this->withSession(['dev_role' => 'admin'])
            ->from(route('dev.admin.users.create'))
            ->post(route('dev.admin.users.store'), [
                'nama' => 'Kepala Sekolah Tanpa NIP',
                'email' => 'kepala-tanpa-nip@example.test',
                'id_role' => $this->kepalaSekolahRole->id_role,
                'status_akun' => 'Aktif',
            ])
            ->assertRedirect(route('dev.admin.users.create'))
            ->assertSessionHasErrors('nip');
    }

    public function test_subject_teacher_form_can_assign_multiple_classes(): void
    {
        $firstClass = Kelas::create([
            'nama_kelas' => 'XI B',
            'tingkat' => 'XI',
            'tahun_ajaran' => '2026/2027',
        ]);
        $secondClass = Kelas::create([
            'nama_kelas' => 'XI C',
            'tingkat' => 'XI',
            'tahun_ajaran' => '2026/2027',
        ]);
        $subject = MataPelajaran::create(['nama_mapel' => 'Matematika']);
        $subject->kelas()->attach([$firstClass->id_kelas, $secondClass->id_kelas]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.create'))
            ->assertOk()
            ->assertSee('Penugasan awal Guru Mata Pelajaran')
            ->assertSee('Mata pelajaran')
            ->assertSee('name="id_kelas[]"', false);

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.users.store'), [
                'nama' => 'Guru Matematika',
                'username' => 'guru-matematika',
                'email' => 'matematika@example.test',
                'id_role' => $this->guruMapelRole->id_role,
                'nip' => '198901012020121002',
                'status_akun' => 'Aktif',
                'id_mapel' => $subject->id_mapel,
                'id_kelas' => [$firstClass->id_kelas, $secondClass->id_kelas],
            ])
            ->assertRedirect(route('dev.admin.users.index'));

        $teacher = Pengguna::where('email', 'matematika@example.test')->firstOrFail();
        $this->assertDatabaseHas('relasi_guru_mapel_kelas', [
            'id_pengguna' => $teacher->id_pengguna,
            'id_mapel' => $subject->id_mapel,
            'id_kelas' => $firstClass->id_kelas,
        ]);
        $this->assertDatabaseHas('relasi_guru_mapel_kelas', [
            'id_pengguna' => $teacher->id_pengguna,
            'id_mapel' => $subject->id_mapel,
            'id_kelas' => $secondClass->id_kelas,
        ]);
    }

    public function test_admin_can_create_a_parent_account_with_a_temporary_password(): void
    {
        $student = $this->makeStudent('S-001', 'Siswa Uji');
        $payload = [
            'nama' => 'Wali Siswa',
            'email' => 'wali@example.test',
            'id_siswa' => $student->id_siswa,
            'hubungan' => 'Ibu',
        ];

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.users.parents.store'), $payload)
            ->assertRedirect(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertSessionHas('temporary_parent_password', 'SiswaS-001')
            ->assertSessionHas('temporary_parent_username', 'ortu_S-001');

        $parent = Pengguna::where('email', $payload['email'])->firstOrFail();
        $this->assertSame($this->parentRole->id_role, $parent->id_role);
        $this->assertSame('ortu_'.$student->nis, $parent->username);
        $this->assertTrue(Hash::check('SiswaS-001', $parent->password));
        $this->assertTrue($parent->wajib_ganti_password);
        $this->assertNull($parent->password_diubah_pada);
        $this->assertDatabaseHas('orang_tua', [
            'id_pengguna' => $parent->id_pengguna,
            'no_telepon' => null,
            'alamat' => null,
        ]);
        $this->assertInstanceOf(OrangTua::class, $parent->orangTua);
        $this->assertDatabaseHas('relasi_orang_tua_siswa', [
            'id_orang_tua' => $parent->orangTua->id_orang_tua,
            'id_siswa' => $student->id_siswa,
            'hubungan' => 'Ibu',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.parents.create'))
            ->assertOk()
            ->assertSee('format ortu_NIS')
            ->assertSee('Username login')
            ->assertSee('for="alamat"', false)
            ->assertDontSee('name="username"', false);

        $this->withSession(['dev_role' => 'admin'])
            ->from(route('dev.admin.users.parents.create'))
            ->post(route('dev.admin.users.parents.store'), [
                'nama' => 'Wali Kedua',
                'email' => 'wali-kedua@example.test',
                'id_siswa' => $student->id_siswa,
                'hubungan' => 'Ayah',
            ])
            ->assertRedirect(route('dev.admin.users.parents.create'))
            ->assertSessionHasErrors('id_siswa');
    }

    public function test_parent_email_is_optional_and_multiple_parents_can_leave_it_empty(): void
    {
        $firstStudent = $this->makeStudent('S-EMAIL-01', 'Siswa Tanpa Email Satu');
        $secondStudent = $this->makeStudent('S-EMAIL-02', 'Siswa Tanpa Email Dua');

        foreach ([$firstStudent, $secondStudent] as $index => $student) {
            $this->withSession(['dev_role' => 'admin'])
                ->post(route('dev.admin.users.parents.store'), [
                    'nama' => 'Wali Tanpa Email '.($index + 1),
                    'id_siswa' => $student->id_siswa,
                    'hubungan' => 'Wali',
                ])
                ->assertRedirect(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
                ->assertSessionDoesntHaveErrors('email');
        }

        $this->assertSame(2, Pengguna::query()
            ->where('id_role', $this->parentRole->id_role)
            ->whereNull('email')
            ->count());

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.parents.create'))
            ->assertOk()
            ->assertSee('Email <span class="text-muted">(opsional)</span>', false);
    }

    public function test_parent_account_can_be_created_from_the_unified_user_form(): void
    {
        $student = $this->makeStudent('S-002', 'Siswa Form Terpadu');

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.create'))
            ->assertOk()
            ->assertSee('Orang Tua')
            ->assertSee('Data Orang Tua/Wali')
            ->assertSee('name="no_telepon"', false)
            ->assertSee('name="alamat"', false);

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.users.store'), [
                'nama' => 'Wali Form Terpadu',
                'id_role' => $this->parentRole->id_role,
                'status_akun' => 'Aktif',
                'id_siswa' => $student->id_siswa,
                'hubungan' => 'Wali',
                'no_telepon' => '081234567890',
                'alamat' => 'Banjarmasin',
            ])
            ->assertRedirect(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertSessionHas('temporary_parent_password');

        $parent = Pengguna::where('username', 'ortu_S-002')->firstOrFail();
        $this->assertNull($parent->email);
        $this->assertDatabaseHas('orang_tua', [
            'id_pengguna' => $parent->id_pengguna,
            'no_telepon' => '081234567890',
            'alamat' => 'Banjarmasin',
        ]);
    }

    public function test_gpk_and_tim_inklusi_can_be_assigned_up_to_two_students(): void
    {
        $firstStudent = $this->makeStudent('S-003', 'Siswa Dampingan Satu');
        $secondStudent = $this->makeStudent('S-004', 'Siswa Dampingan Dua');
        $thirdStudent = $this->makeStudent('S-005', 'Siswa Dampingan Tiga');

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.users.store'), [
                'nama' => 'GPK Pendamping',
                'email' => 'gpk-pendamping@example.test',
                'id_role' => $this->gpkRole->id_role,
                'nip' => '198901012020121099',
                'status_akun' => 'Aktif',
                'id_siswa_pendamping' => [$firstStudent->id_siswa, $secondStudent->id_siswa],
            ])
            ->assertRedirect(route('dev.admin.users.index'));

        $gpk = Pengguna::where('email', 'gpk-pendamping@example.test')->firstOrFail();
        $this->assertDatabaseHas('relasi_pendamping_siswa', [
            'id_pengguna' => $gpk->id_pengguna,
            'id_siswa' => $firstStudent->id_siswa,
        ]);
        $this->assertDatabaseHas('relasi_pendamping_siswa', [
            'id_pengguna' => $gpk->id_pengguna,
            'id_siswa' => $secondStudent->id_siswa,
        ]);

        $timInklusiRole = Role::create(['nama_role' => 'Tim Inklusi']);

        $this->withSession(['dev_role' => 'admin'])
            ->from(route('dev.admin.users.create'))
            ->post(route('dev.admin.users.store'), [
                'nama' => 'Tim Inklusi Berlebih',
                'email' => 'tim-inklusi-berlebih@example.test',
                'id_role' => $timInklusiRole->id_role,
                'nip' => '198901012020121100',
                'status_akun' => 'Aktif',
                'id_siswa_pendamping' => [$firstStudent->id_siswa, $secondStudent->id_siswa, $thirdStudent->id_siswa],
            ])
            ->assertRedirect(route('dev.admin.users.create'))
            ->assertSessionHasErrors('id_siswa_pendamping');

        $this->assertDatabaseMissing('pengguna', ['email' => 'tim-inklusi-berlebih@example.test']);

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.users.store'), [
                'nama' => 'Tim Inklusi Dua Siswa',
                'email' => 'tim-inklusi-dua@example.test',
                'id_role' => $timInklusiRole->id_role,
                'nip' => '198901012020121101',
                'status_akun' => 'Aktif',
                'id_siswa_pendamping' => [$firstStudent->id_siswa, $thirdStudent->id_siswa],
            ])
            ->assertRedirect(route('dev.admin.users.index'));

        $timInklusi = Pengguna::where('email', 'tim-inklusi-dua@example.test')->firstOrFail();
        $this->assertDatabaseHas('relasi_pendamping_siswa', [
            'id_pengguna' => $timInklusi->id_pengguna,
            'id_siswa' => $firstStudent->id_siswa,
        ]);
        $this->assertDatabaseHas('relasi_pendamping_siswa', [
            'id_pengguna' => $timInklusi->id_pengguna,
            'id_siswa' => $thirdStudent->id_siswa,
        ]);
    }

    public function test_admin_can_reset_parent_password_from_the_parent_filter(): void
    {
        $student = $this->makeStudent('SISWA-RESET', 'Budi Reset');
        $parent = $this->makeUser('Wali Reset', 'wali-reset@example.test', $this->parentRole);
        $parent->update([
            'username' => 'SISWA-RESET',
            'password' => Hash::make('password-lama'),
        ]);
        $parentProfile = OrangTua::create(['id_pengguna' => $parent->id_pengguna]);
        RelasiOrangTuaSiswa::create([
            'id_orang_tua' => $parentProfile->id_orang_tua,
            'id_siswa' => $student->id_siswa,
            'hubungan' => 'Ayah',
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertOk()
            ->assertSee('Username &amp; Keamanan', false)
            ->assertSee('Username:')
            ->assertSee('Password:')
            ->assertSee('Tidak dapat ditampilkan; gunakan Reset Password.')
            ->assertSee('Reset Password');

        $response = $this->withSession(['dev_role' => 'admin'])
            ->patch(route('dev.admin.users.reset-password', $parent));

        $response
            ->assertRedirect(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertSessionHas('temporary_parent_password');

        $temporaryPassword = session('temporary_parent_password');
        $parent->refresh();

        $this->assertSame('BudiSISWA-RESET', $temporaryPassword);
        $this->assertSame('ortu_SISWA-RESET', $parent->username);
        $this->assertTrue(Hash::check($temporaryPassword, $parent->password));
        $this->assertTrue($parent->wajib_ganti_password);
        $this->assertNull($parent->password_diubah_pada);
        $this->assertNotSame('password-lama', $parent->password);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertOk()
            ->assertSee('Username:</strong> ortu_SISWA-RESET', false)
            ->assertSee('Password Sementara:</strong> BudiSISWA-RESET', false);
    }

    public function test_permanent_user_deletion_is_not_offered_or_routed(): void
    {
        $staff = $this->makeUser('Akun Dipertahankan', 'dipertahankan@example.test', $this->gpkRole);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index'))
            ->assertOk()
            ->assertDontSee('Hapus akun secara permanen')
            ->assertSee('Nonaktifkan');

        $this->withSession(['dev_role' => 'admin'])
            ->delete("/_dev/admin/pengguna/{$staff->id_pengguna}")
            ->assertStatus(405);

        $this->assertDatabaseHas('pengguna', ['id_pengguna' => $staff->id_pengguna]);
    }

    public function test_last_active_admin_cannot_be_disabled_or_changed_to_another_role(): void
    {
        $admin = $this->makeUser('Admin Tunggal', 'admin-tunggal@example.test', $this->adminRole);

        $this->withSession(['dev_role' => 'admin'])
            ->patch(route('dev.admin.users.status', $admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pengguna', ['id_pengguna' => $admin->id_pengguna, 'status_akun' => 'Aktif']);

        $this->withSession(['dev_role' => 'admin'])
            ->from(route('dev.admin.users.edit', $admin))
            ->put(route('dev.admin.users.update', $admin), [
                'nama' => $admin->nama,
                'email' => $admin->email,
                'id_role' => $this->adminRole->id_role,
                'status_akun' => 'Nonaktif',
            ])
            ->assertSessionHasErrors('status_akun');

        $this->assertDatabaseHas('pengguna', ['id_pengguna' => $admin->id_pengguna, 'status_akun' => 'Aktif']);
    }

    private function makeUser(string $name, string $email, Role $role): Pengguna
    {
        return Pengguna::create([
            'nama' => $name,
            'email' => $email,
            'id_role' => $role->id_role,
            'nip' => in_array($role->nama_role, ['GPK', 'Kepala Sekolah'], true) ? '197001012020121001' : null,
            'password' => 'password',
            'status_akun' => 'Aktif',
        ]);
    }

    private function makeStudent(string $nis, string $name): Siswa
    {
        $class = Kelas::firstOrCreate([
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'tahun_ajaran' => '2026/2027',
        ]);

        return Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => $nis,
            'nama_siswa' => $name,
            'status_siswa' => 'Aktif',
        ]);
    }
}
