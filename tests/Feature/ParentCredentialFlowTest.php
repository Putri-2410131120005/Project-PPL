<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Role;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentCredentialFlowTest extends TestCase
{
    use RefreshDatabase;

    private Role $parentRole;

    private Siswa $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parentRole = Role::create(['nama_role' => 'Orang Tua']);
        $class = Kelas::create([
            'nama_kelas' => 'VII Kredensial',
            'tingkat' => 'VII',
            'tahun_ajaran' => '2026/2027',
        ]);
        $this->student = Siswa::create([
            'id_kelas' => $class->id_kelas,
            'nis' => '12345',
            'nama_siswa' => 'Ahmad Pratama',
            'status_siswa' => 'Aktif',
        ]);
    }

    public function test_parent_credentials_are_derived_from_the_linked_student_and_visible_while_valid(): void
    {
        $this->createParentAccount();

        $parent = Pengguna::where('username', 'ortu_12345')->firstOrFail();
        $this->assertNull($parent->email);
        $this->assertSame('ortu_12345', $parent->username);
        $this->assertTrue(Hash::check('Ahmad12345', $parent->password));
        $this->assertTrue($parent->wajib_ganti_password);
        $this->assertNull($parent->password_diubah_pada);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertOk()
            ->assertSee('Username:</strong> ortu_12345', false)
            ->assertSee('Password Sementara:</strong> Ahmad12345', false);
    }

    public function test_first_parent_login_requires_password_change_and_disables_the_temporary_password(): void
    {
        $this->createParentAccount();

        $this->post(route('dev.parent.login.store'), [
            'username' => 'ortu_12345',
            'password' => 'Ahmad12345',
        ])->assertRedirect(route('dev.parent.password.edit'));

        $this->withSession(['dev_role' => 'orang-tua'])
            ->get(route('dev.preview', 'orang-tua'))
            ->assertRedirect(route('dev.parent.password.edit'));

        $this->put(route('dev.parent.password.update'), [
            'password' => 'PasswordBaru!123',
            'password_confirmation' => 'PasswordBaru!123',
        ])->assertRedirect(route('dev.preview', 'orang-tua'));

        $parent = Pengguna::where('username', 'ortu_12345')->firstOrFail();
        $this->assertFalse($parent->wajib_ganti_password);
        $this->assertNotNull($parent->password_diubah_pada);
        $this->assertTrue(Hash::check('PasswordBaru!123', $parent->password));
        $this->assertFalse(Hash::check('Ahmad12345', $parent->password));

        $this->post(route('dev.logout'))->assertRedirect(route('dev.login'));
        $this->assertFalse(Auth::guard('parent')->check());

        $this->from(route('dev.parent.login'))
            ->post(route('dev.parent.login.store'), [
                'username' => 'ortu_12345',
                'password' => 'Ahmad12345',
            ])
            ->assertRedirect(route('dev.parent.login'))
            ->assertSessionHasErrors('username');

        $this->post(route('dev.parent.login.store'), [
            'username' => 'ortu_12345',
            'password' => 'PasswordBaru!123',
        ])->assertRedirect(route('dev.preview', 'orang-tua'));

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertOk()
            ->assertSee('Password:</strong> Sudah diubah', false)
            ->assertDontSee('Ahmad12345');
    }

    public function test_admin_does_not_see_a_computed_password_when_the_hash_does_not_match(): void
    {
        $this->createParentAccount();
        session()->forget(['temporary_parent_password', 'temporary_parent_username', 'temporary_parent_email']);
        Pengguna::where('username', 'ortu_12345')->update([
            'password' => Hash::make('password-lain'),
            'wajib_ganti_password' => true,
        ]);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertOk()
            ->assertDontSee('Ahmad12345')
            ->assertSee('Tidak dapat ditampilkan; gunakan Reset Password.');
    }

    private function createParentAccount(): void
    {
        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.admin.users.parents.store'), [
                'nama' => 'Wali Ahmad',
                'email' => 'wali-ahmad@example.test',
                'id_siswa' => $this->student->id_siswa,
                'hubungan' => 'Ayah',
            ])
            ->assertRedirect(route('dev.admin.users.index', ['role' => $this->parentRole->id_role]))
            ->assertSessionHas('temporary_parent_username', 'ortu_12345')
            ->assertSessionHas('temporary_parent_password', 'Ahmad12345');
    }
}
