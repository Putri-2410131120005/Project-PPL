<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'Admin / Pengelola',
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
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $credentialsFile = tempnam(sys_get_temp_dir(), 'integration-a16-');
        file_put_contents($credentialsFile, json_encode(array_fill_keys(
            ['demo.a16.guru1', 'demo.a16.guru2', 'demo.a16.gpk'], bin2hex(random_bytes(16))
        )));
        putenv('A16_DEMO_CREDENTIALS_FILE='.$credentialsFile);
        try {
            $this->seed(\Database\Seeders\A16DemoSeeder::class);
        } finally {
            putenv('A16_DEMO_CREDENTIALS_FILE');
            unlink($credentialsFile);
        }
        $timInklusiRole = Role::where('nama_role', 'Tim Inklusi')->sole();
        $timInklusi = Pengguna::create([
            'id_role' => $timInklusiRole->getKey(),
            'nama' => 'Pendamping Development',
            'username' => 'pendamping-development',
            'email' => 'pendamping-development@example.test',
            'password' => 'password',
            'status_akun' => 'Aktif',
        ]);
        $gpk = Pengguna::where('username', 'demo.a16.gpk')->sole();

        foreach (['admin', 'guru-mapel', 'tim-inklusi', 'gpk', 'kepala-sekolah'] as $role) {
            $payload = ['role' => $role];
            if ($role === 'tim-inklusi') {
                $payload['pendamping_id'] = $timInklusi->getKey();
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
