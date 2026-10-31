<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google', [
            'client_id' => 'google-client-for-tests',
            'client_secret' => 'google-secret-for-tests',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    private function role(string $name): int
    {
        return DB::table('role')->insertGetId(['nama_role' => $name], 'id_role');
    }

    private function account(string $role, string $email, array $overrides = []): Pengguna
    {
        $account = Pengguna::create(array_merge([
            'id_role' => $this->role($role),
            'nama' => 'Pengguna '.$role,
            'username' => str($role)->slug().random_int(1000, 9999),
            'email' => $email,
            'password' => 'internal-password-only',
            'status_akun' => 'Aktif',
        ], $overrides));

        if (array_key_exists('is_super_admin', $overrides)) {
            $account->forceFill(['is_super_admin' => $overrides['is_super_admin']])->save();
        }

        return $account;
    }

    private function fakeGoogle(string $id, string $email, bool $verified = true): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => $id,
            'email' => $email,
            'email_verified' => $verified,
        ]));
    }

    private function googleCallback(string $role)
    {
        return $this->withSession(['google_login_role' => $role])
            ->get(route('auth.google.callback'));
    }

    public function test_general_login_lists_five_staff_roles_and_keeps_parent_and_development_links(): void
    {
        $response = $this->get(route('login'))
            ->assertOk()
            ->assertSee('Pilih akses staf')
            ->assertSee('Kepala Sekolah')
            ->assertSee('Guru Mata Pelajaran')
            ->assertSee('Guru Pendamping Khusus (GPK)')
            ->assertSee('Tim Inklusi')
            ->assertSee('Super Admin')
            ->assertSee('Masuk sebagai Orang Tua/Wali')
            ->assertSee('Login development');

        $response->assertSee(route('dev.login'), false);
    }

    public function test_each_role_login_uses_shared_design_and_expected_copy(): void
    {
        $cases = [
            ['auth.role.principal.login', 'Masuk Kepala Sekolah', 'Selamat datang, Kepala Sekolah'],
            ['guru-mapel.login', 'Masuk Guru Mata Pelajaran', 'Selamat datang, Guru Mata Pelajaran'],
            ['auth.role.gpk.login', 'Masuk Guru Pendamping Khusus', 'Selamat datang, GPK'],
            ['auth.role.tim-inklusi.login', 'Masuk Tim Inklusi', 'Selamat datang, Tim Inklusi'],
            ['auth.role.admin.login', 'Masuk Super Admin', 'Selamat datang, Super Admin'],
        ];

        foreach ($cases as [$route, $pageTitle, $formTitle]) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($pageTitle)
                ->assertSee($formTitle)
                ->assertSee('Masuk dengan Google')
                ->assertSee('abk-parent-access-frame', false)
                ->assertSee('Login development');
        }
    }

    public function test_role_redirect_stores_allowlisted_context_and_uses_existing_socialite_driver(): void
    {
        Socialite::fake('google');

        $this->get(route('auth.google.role.redirect', 'gpk'))
            ->assertRedirect()
            ->assertSessionHas('google_login_role', 'gpk');

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_missing_google_configuration_fails_safely_on_selected_role_page(): void
    {
        config()->set('services.google.client_secret', null);

        $this->get(route('auth.role.gpk.login'))->assertOk()->assertSee('Google Login belum dikonfigurasi.');
        $this->get(route('auth.google.role.redirect', 'gpk'))
            ->assertRedirect(route('auth.role.gpk.login'))
            ->assertSessionHasErrors('google');
    }

    public function test_first_login_links_registered_active_account_and_next_login_uses_google_id(): void
    {
        $account = $this->account('GPK', 'gpk@sekolah.test');
        $this->fakeGoogle('google-gpk-1', 'gpk@sekolah.test');

        $this->googleCallback('gpk')->assertRedirect(route('staff.gpk.dashboard'));
        $this->assertAuthenticatedAs($account, 'staff');
        $this->assertSame('google-gpk-1', $account->fresh()->google_id);

        $account->update(['email' => 'email-baru@sekolah.test']);
        $this->post(route('logout'));
        $this->fakeGoogle('google-gpk-1', 'alamat-google-berubah@gmail.com');
        $this->googleCallback('gpk')->assertRedirect(route('staff.gpk.dashboard'));
        $this->assertAuthenticatedAs($account, 'staff');
    }

    public function test_each_internal_role_is_redirected_to_its_existing_dashboard(): void
    {
        $cases = [
            ['gpk', 'GPK', 'staff.gpk.dashboard', []],
            ['tim-inklusi', 'Tim Inklusi', 'staff.tim-inklusi.dashboard', []],
            ['guru-mapel', 'Guru Mapel', 'guru-mapel.dashboard', []],
            ['kepala-sekolah', 'Kepala Sekolah', 'staff.principal.dashboard', []],
            ['admin', 'Admin', 'staff.admin.dashboard', ['is_super_admin' => true]],
        ];

        foreach ($cases as $index => [$context, $role, $route, $overrides]) {
            $account = $this->account($role, "role{$index}@sekolah.test", $overrides);
            $this->fakeGoogle("google-role-{$index}", $account->email);
            $this->googleCallback($context)->assertRedirect(route($route));
            $this->post(route('logout'));
        }
    }

    public function test_role_context_mismatch_is_rejected_before_google_id_is_linked(): void
    {
        $principal = $this->account('Kepala Sekolah', 'kepala@sekolah.test');
        $this->fakeGoogle('google-kepala', $principal->email);

        $this->googleCallback('gpk')
            ->assertRedirect(route('auth.role.gpk.login'))
            ->assertSessionHasErrors('google');

        $this->assertNull($principal->fresh()->google_id);
        $this->assertGuest('staff');
    }

    public function test_callback_without_valid_context_is_rejected_without_creating_session(): void
    {
        $this->fakeGoogle('google-no-context', 'nobody@sekolah.test');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest('staff');
    }

    public function test_unknown_inactive_and_unverified_accounts_are_rejected_without_linking(): void
    {
        $this->fakeGoogle('google-unknown', 'unknown@sekolah.test');
        $this->googleCallback('gpk')->assertSessionHasErrors('google');
        $this->assertDatabaseCount('pengguna', 0);

        $inactive = $this->account('GPK', 'inactive@sekolah.test', ['status_akun' => 'Nonaktif']);
        $this->fakeGoogle('google-inactive', $inactive->email);
        $this->googleCallback('gpk')->assertSessionHasErrors('google');
        $this->assertNull($inactive->fresh()->google_id);

        $active = $this->account('Guru Mapel', 'guru@sekolah.test');
        $this->fakeGoogle('google-unverified', $active->email, false);
        $this->googleCallback('guru-mapel')->assertSessionHasErrors('google');
        $this->assertNull($active->fresh()->google_id);
        $this->assertGuest('staff');
    }

    public function test_different_google_id_cannot_take_over_linked_account(): void
    {
        $account = $this->account('Tim Inklusi', 'tim@sekolah.test', ['google_id' => 'google-original']);
        $this->fakeGoogle('google-attacker', $account->email);

        $this->googleCallback('tim-inklusi')->assertSessionHasErrors('google');
        $this->assertSame('google-original', $account->fresh()->google_id);
        $this->assertGuest('staff');
    }

    public function test_admin_role_without_super_admin_flag_is_rejected(): void
    {
        $account = $this->account('Admin', 'admin-biasa@sekolah.test');
        $this->fakeGoogle('google-admin-biasa', $account->email);

        $this->googleCallback('admin')->assertSessionHasErrors('google');
        $this->assertNull($account->fresh()->google_id);
        $this->assertGuest('staff');
    }

    public function test_logged_in_account_cannot_gain_another_role_from_a_different_login_page(): void
    {
        $principal = $this->account('Kepala Sekolah', 'principal@sekolah.test', ['google_id' => 'google-principal']);

        $this->actingAs($principal, 'staff')
            ->get(route('auth.role.gpk.login'))
            ->assertOk()
            ->assertSee('Anda masih masuk dengan role lain')
            ->assertSee('aria-disabled="true"', false);

        $this->assertAuthenticatedAs($principal, 'staff');
    }

    public function test_google_principal_opens_existing_principal_dashboard_without_dev_role_session(): void
    {
        $account = $this->account('Kepala Sekolah', 'kepala-google@sekolah.test', [
            'google_id' => 'google-kepala-existing-dashboard',
        ]);

        $this->actingAs($account, 'staff')
            ->get(route('staff.principal.dashboard'))
            ->assertOk();

        $this->assertFalse(session()->has('dev_role'));
    }

    public function test_role_middleware_blocks_cross_role_and_development_session_cannot_open_google_dashboard(): void
    {
        $gpk = $this->account('GPK', 'gpk-role@sekolah.test', ['google_id' => 'google-role-gpk']);
        $this->actingAs($gpk, 'staff')->get(route('staff.admin.dashboard'))->assertRedirect(route('login'));

        $this->withSession(['dev_role' => 'gpk', 'dev_pengguna_id' => $gpk->getKey()])
            ->get(route('staff.gpk.dashboard'))->assertRedirect(route('login'));
    }

    public function test_logout_clears_staff_session_and_inactive_account_loses_existing_access(): void
    {
        $account = $this->account('Kepala Sekolah', 'kepala-logout@sekolah.test', ['google_id' => 'google-kepala']);
        $this->actingAs($account, 'staff')->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest('staff');

        $this->actingAs($account, 'staff');
        $account->update(['status_akun' => 'Nonaktif']);
        $this->get(route('staff.principal.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest('staff');
    }
}
