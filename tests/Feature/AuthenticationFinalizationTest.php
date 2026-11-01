<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_login_has_credentials_and_google_without_a_role_selector(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('name="password"', false)
            ->assertSee('Masuk dengan Google')
            ->assertDontSee('data-development-role', false)
            ->assertDontSee('Pilih peran');
    }

    public function test_development_login_routes_and_urls_are_gone(): void
    {
        $this->assertFalse(Route::has('dev.login'));
        $this->assertFalse(Route::has('dev.login.store'));
        $this->assertFalse(Route::has('dev.change-role'));
        $this->get('/_dev')->assertNotFound();
        $this->get('/_dev/login')->assertNotFound();
        $this->post('/_dev/login')->assertNotFound();
    }

    public function test_all_dashboards_require_real_authentication(): void
    {
        foreach ([
            'dev.preview',
            'dev.gpk.dashboard',
            'dev.tim-inklusi.dashboard',
            'guru-mapel.dashboard',
            'dev.principal.dashboard',
            'dev.parent.dashboard',
        ] as $route) {
            $this->followingRedirects()->get(route($route))
                ->assertOk()
                ->assertSee('Masuk SMART-ABK');
        }
    }

    public function test_database_role_cannot_open_another_role_dashboard(): void
    {
        $gpk = $this->staff('GPK', 'gpk-role-test@example.test');
        $this->actingAs($gpk, 'staff')->get(route('dev.gpk.dashboard'))->assertOk();

        $this->get(route('dev.tim-inklusi.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest('staff');
    }

    public function test_logout_invalidates_every_auth_guard_and_protected_pages_are_not_cached(): void
    {
        $guru = $this->staff('Guru Mapel', 'guru-logout-test@example.test');
        $this->actingAs($guru, 'staff')->actingAs($guru, 'guru_mapel');
        $oldSessionId = session()->getId();
        $oldToken = session()->token();

        $this->get(route('guru-mapel.dashboard'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest('staff');
        $this->assertGuest('guru_mapel');
        $this->assertGuest('parent');
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->get(route('guru-mapel.dashboard'))->assertRedirect(route('guru-mapel.login'));
    }

    public function test_logout_works_for_every_database_role(): void
    {
        $staffCases = [
            ['Admin / Pengelola', 'admin-logout@example.test', 'dev.preview', true],
            ['GPK', 'gpk-logout@example.test', 'dev.gpk.dashboard', false],
            ['Tim Inklusi', 'tim-logout@example.test', 'dev.tim-inklusi.dashboard', false],
            ['Guru Mapel', 'guru-semua-logout@example.test', 'guru-mapel.dashboard', false],
            ['Kepala Sekolah', 'kepala-logout@example.test', 'dev.principal.dashboard', false],
        ];

        foreach ($staffCases as [$role, $email, $dashboard, $superAdmin]) {
            $account = $this->staff($role, $email);
            if ($superAdmin) {
                $account->forceFill(['is_super_admin' => true])->save();
            }
            $this->actingAs($account, 'staff');
            if ($role === 'Guru Mapel') {
                $this->actingAs($account, 'guru_mapel');
            }

            $this->get(route($dashboard))->assertOk();
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest('staff');
            $this->assertGuest('guru_mapel');
        }

        $parentRole = Role::query()->create(['nama_role' => 'Orang Tua']);
        $parent = Pengguna::query()->create([
            'id_role' => $parentRole->getKey(),
            'nama' => 'Wali Logout',
            'username' => 'wali-logout',
            'password' => 'password-parent',
            'wajib_ganti_password' => false,
            'status_akun' => 'Aktif',
        ]);
        $this->actingAs($parent, 'parent')->get(route('dev.parent.dashboard'))->assertOk();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest('parent');
    }

    private function staff(string $roleName, string $email): Pengguna
    {
        $role = Role::query()->create(['nama_role' => $roleName]);

        return Pengguna::query()->create([
            'id_role' => $role->getKey(),
            'nama' => 'Pengguna '.$roleName,
            'email' => $email,
            'google_id' => 'google-'.sha1($email),
            'password' => 'testing-password',
            'status_akun' => 'Aktif',
        ]);
    }
}
