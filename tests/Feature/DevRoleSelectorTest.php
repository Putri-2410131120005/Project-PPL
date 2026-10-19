<?php

namespace Tests\Feature;

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
                'Orang Tua',
            ]);
    }

    public function test_each_role_can_be_selected_and_open_its_own_dashboard(): void
    {
        foreach (['admin', 'guru-mapel', 'tim-inklusi', 'gpk', 'kepala-sekolah', 'orang-tua'] as $role) {
            $this->post(route('dev.login.store'), ['role' => $role])
                ->assertRedirect(route('dev.preview', $role))
                ->assertSessionHas('dev_role', $role);

            $this->withSession(['dev_role' => $role])
                ->get(route('dev.preview', $role))
                ->assertOk();
        }
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
