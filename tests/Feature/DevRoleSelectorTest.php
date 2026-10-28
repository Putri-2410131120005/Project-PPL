<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DevRoleSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_lists_all_development_roles(): void
    {
        $this->get(route('dev.login'))
            ->assertOk()
            ->assertSee('Pilih peran untuk membuka area kerja SMART-ABK')
            ->assertSeeInOrder([
                'Super Admin',
                'Guru Mata Pelajaran',
                'Tim Inklusi',
                'GPK',
                'Kepala Sekolah',
            ])
            ->assertDontSee('<option value="orang-tua"', false)
            ->assertSee('Buka login Orang Tua');
    }

    public function test_each_role_can_be_selected_and_open_its_own_dashboard(): void
    {
        $this->seed(RoleSeeder::class);
        $adminRole = Role::whereIn('nama_role', ['Admin', 'Admin / Pengelola'])->sole();
        $guruRole = Role::where('nama_role', 'Guru Mapel')->sole();
        $timInklusiRole = Role::where('nama_role', 'Tim Inklusi')->sole();
        $gpkRole = Role::where('nama_role', 'GPK')->sole();
        $classId = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'X-A', 'tingkat' => 'X', 'tahun_ajaran' => '2026/2027', 'status_kelas' => 'Aktif',
        ], 'id_kelas');
        $subjectId = DB::table('mata_pelajaran')->insertGetId([
            'nama_mapel' => 'Matematika', 'status_mapel' => 'Aktif',
        ], 'id_mapel');
        $guruMapel = Pengguna::create([
            'id_role' => $guruRole->getKey(), 'nama' => 'Budi Santoso', 'username' => 'budi_santoso',
            'email' => 'budi.santoso@sekolah.test', 'password' => 'password', 'status_akun' => 'Aktif',
        ]);
        DB::table('relasi_guru_mapel_kelas')->insert([
            'id_pengguna' => $guruMapel->getKey(), 'id_mapel' => $subjectId, 'id_kelas' => $classId,
        ]);
        $timInklusi = Pengguna::create([
            'id_role' => $timInklusiRole->getKey(),
            'nama' => 'Pendamping Development',
            'username' => 'pendamping-development',
            'email' => 'pendamping-development@example.test',
            'password' => 'password',
            'status_akun' => 'Aktif',
        ]);
        $gpk = Pengguna::create([
            'id_role' => $gpkRole->getKey(), 'nama' => 'Dedi Kurniawan', 'username' => 'dedi_kurniawan',
            'email' => 'dedi.kurniawan@sekolah.test', 'password' => 'password', 'status_akun' => 'Aktif',
        ]);
        $superAdmin = Pengguna::create([
            'id_role' => $adminRole->getKey(), 'nama' => 'Super Admin Sekolah', 'username' => 'superadmin',
            'email' => 'superadmin@sekolah.test', 'password' => 'password', 'status_akun' => 'Aktif',
        ]);
        $superAdmin->forceFill(['is_super_admin' => true])->save();

        foreach (['admin', 'guru-mapel', 'tim-inklusi', 'gpk', 'kepala-sekolah'] as $role) {
            $payload = ['role' => $role];
            if ($role === 'tim-inklusi') {
                $payload['pendamping_id'] = $timInklusi->getKey();
            }
            if ($role === 'guru-mapel') {
                $payload['guru_mapel_id'] = $guruMapel->getKey();
            }
            if ($role === 'gpk') {
                $payload['gpk_id'] = $gpk->getKey();
            }

            $this->post(route('dev.login.store'), $payload)
                ->assertRedirect(route('dev.preview', $role))
                ->assertSessionHas('dev_role', $role);

            $preview = $this->withSession(['dev_role' => $role])
                ->get(route('dev.preview', $role));

            if ($role === 'tim-inklusi') {
                $preview->assertRedirect(route('dev.tim-inklusi.dashboard'));
                $this->get(route('dev.tim-inklusi.dashboard'))->assertOk();
            } elseif ($role === 'gpk') {
                $preview->assertRedirect(route('dev.gpk.dashboard'));
                $this->get(route('dev.gpk.dashboard'))->assertOk();
            } elseif ($role === 'guru-mapel') {
                $preview->assertRedirect(route('dev.guru-mapel.dashboard'));
                $this->get(route('dev.guru-mapel.dashboard'))->assertOk();
            } elseif ($role === 'kepala-sekolah') {
                $preview->assertRedirect(route('dev.principal.dashboard'));
                $this->get(route('dev.principal.dashboard'))->assertOk();
            } else {
                $preview->assertOk();
            }
        }
    }

    public function test_parent_role_cannot_be_selected_from_development_login(): void
    {
        $this->from(route('dev.login'))
            ->post(route('dev.login.store'), ['role' => 'orang-tua'])
            ->assertRedirect(route('dev.login'))
            ->assertSessionHasErrors('role')
            ->assertSessionMissing('dev_role');
    }

    public function test_dashboard_requires_a_selected_role(): void
    {
        $this->get(route('dev.preview', 'admin'))
            ->assertRedirect(route('dev.login'));
    }

    public function test_selected_role_cannot_open_another_dashboard_by_changing_the_url(): void
    {
        $this->withSession(['dev_role' => 'admin'])
            ->get(route('dev.preview', 'guru-mapel'))
            ->assertRedirect(route('dev.preview', 'admin'));
    }

    public function test_invalid_role_is_rejected(): void
    {
        $this->from(route('dev.login'))
            ->post(route('dev.login.store'), ['role' => 'not-a-role'])
            ->assertRedirect(route('dev.login'))
            ->assertSessionHasErrors('role')
            ->assertSessionMissing('dev_role');
    }

    public function test_change_role_and_logout_remove_the_temporary_role(): void
    {
        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.change-role'))
            ->assertRedirect(route('dev.login'))
            ->assertSessionMissing('dev_role');

        $this->withSession(['dev_role' => 'admin'])
            ->post(route('dev.logout'))
            ->assertRedirect(route('dev.login'))
            ->assertSessionMissing('dev_role');
    }
}
