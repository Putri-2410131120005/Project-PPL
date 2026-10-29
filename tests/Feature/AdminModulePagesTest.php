<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulePagesTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['nama_role' => 'Admin / Pengelola']);
        $this->superAdmin = Pengguna::create([
            'id_role' => $role->getKey(), 'nama' => 'Super Admin Sekolah', 'username' => 'superadmin',
            'email' => 'superadmin@example.test', 'password' => 'password', 'status_akun' => 'Aktif',
        ]);
        $this->superAdmin->forceFill(['is_super_admin' => true])->save();
    }

    public function test_admin_can_open_every_registered_admin_page(): void
    {
        $urls = [
            route('dev.preview', 'admin'),
            route('dev.admin.academic-years.index'),
            route('dev.admin.academic-years.create'),
            route('dev.admin.users.index'),
            route('dev.admin.users.create'),
            route('dev.admin.staff.index'),
            route('dev.admin.staff.create'),
            route('dev.admin.students.index'),
            route('dev.admin.students.create'),
            route('dev.admin.parents.index'),
            route('dev.admin.users.parents.create'),
            route('dev.admin.classes.index'),
            route('dev.admin.classes.create'),
            route('dev.admin.subjects.index'),
            route('dev.admin.subjects.create'),
            route('dev.admin.mappings.guru-mapel-kelas.index'),
            route('dev.admin.mappings.pendamping-siswa.index'),
            route('dev.admin.mappings.orang-tua-siswa.index'),
            route('dev.admin.mappings.access-rights.index'),
        ];

        foreach ($urls as $url) {
            $this->withSession(['dev_role' => 'admin', 'dev_pengguna_id' => $this->superAdmin->getKey()])
                ->get($url)
                ->assertOk()
                ->assertSee('INKLUSI')
                ->assertDontSee('Development only');
        }
    }

    public function test_user_form_keeps_common_fields_in_a_consistent_order_for_every_role(): void
    {
        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.create'))
            ->assertOk()
            ->assertSeeInOrder([
                'id="id_role"',
                'id="nama"',
                'id="email"',
                'id="status_akun"',
                'id="username"',
                'id="nip"',
                'id="teaching-assignment-fields"',
                'id="companion-assignment-fields"',
                'id="parent-fields"',
            ], false);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.create'))
            ->assertSeeInOrder([
                'const syncRoleFields = () =>',
                "const isGpk = selectedRole?.dataset.isGpk === 'true';",
                "gpkPositionField.classList.toggle('d-none', !isGpk);",
            ], false);
    }

    public function test_every_user_creation_entry_uses_the_unified_role_driven_form(): void
    {
        foreach ([
            route('dev.admin.users.create'),
            route('dev.admin.staff.create'),
            route('dev.admin.users.parents.create'),
        ] as $url) {
            $this->withSession(['dev_role' => 'admin'])
                ->get($url)
                ->assertOk()
                ->assertSee('Pilih role pengguna terlebih dahulu')
                ->assertSee('id="id_role"', false)
                ->assertSee('id="teaching-assignment-fields"', false)
                ->assertSee('id="companion-assignment-fields"', false)
                ->assertSee('id="parent-fields"', false);
        }
    }

    public function test_updated_admin_forms_show_the_requested_fields(): void
    {
        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.classes.create'))
            ->assertOk()
            ->assertSee('id="tingkat" name="tingkat" required', false)
            ->assertSeeInOrder(['value="X"', 'value="XI"', 'value="XII"'], false);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.students.create'))
            ->assertOk()
            ->assertSee('id="kebutuhan_khusus" name="kebutuhan_khusus"', false)
            ->assertSee('name="kebutuhan_khusus" value="" required', false);

        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.admin.users.create'))
            ->assertOk()
            ->assertDontSee('companion-selection-count')
            ->assertDontSee('/2 siswa dipilih');
    }
}
