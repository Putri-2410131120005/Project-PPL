<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_every_registered_admin_page(): void
    {
        $urls = [
            route('dev.preview', 'admin'),
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
            $this->withSession(['dev_role' => 'admin'])
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
                'id="nama"',
                'id="email"',
                'id="id_role"',
                'id="status_akun"',
                'id="username"',
                'id="nip"',
                'id="teaching-assignment-fields"',
                'id="companion-assignment-fields"',
                'id="parent-fields"',
            ], false);
    }
}
