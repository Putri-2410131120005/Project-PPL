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

    private function account(string $role, string $email, array $overrides = []): Pengguna
    {
        $roleId = DB::table('role')->insertGetId(['nama_role' => $role], 'id_role');
        $account = Pengguna::create(array_merge([
            'id_role' => $roleId,
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

    public function test_login_page_has_parent_credentials_and_one_google_button_without_role_picker(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('name="password"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-controls="password"', false)
            ->assertSee('<button type="submit">Masuk</button>', false)
            ->assertSee('Masuk dengan Google')
            ->assertSee(route('auth.google.redirect'), false)
            ->assertDontSee('Pilih akses staf');
    }

    public function test_google_redirect_no_longer_requires_a_role_selection(): void
    {
        Socialite::fake('google');

        $this->get(route('auth.google.redirect'))
            ->assertRedirect()
            ->assertSessionMissing('google_login_role');
    }

    public function test_legacy_role_login_urls_return_to_the_unified_login(): void
    {
        foreach (['auth.role.principal.login', 'guru-mapel.login', 'auth.role.gpk.login', 'auth.role.tim-inklusi.login', 'auth.role.admin.login'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_missing_google_configuration_fails_safely(): void
    {
        config()->set('services.google.client_secret', null);

        $this->get(route('login'))->assertOk()->assertSee('Google Login belum dikonfigurasi.');
        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_first_google_login_links_registered_email_and_redirects_by_database_role(): void
    {
        $account = $this->account('GPK', 'gpk@sekolah.test');
        $this->fakeGoogle('google-gpk-1', $account->email);

        $this->get(route('auth.google.callback'))->assertRedirect(route('dev.gpk.dashboard'));
        $this->assertAuthenticatedAs($account, 'staff');
        $this->assertSame('google-gpk-1', $account->fresh()->google_id);
    }

    public function test_each_staff_role_is_redirected_automatically(): void
    {
        $cases = [
            ['GPK', 'dev.gpk.dashboard', []],
            ['Tim Inklusi', 'dev.tim-inklusi.dashboard', []],
            ['Guru Mapel', 'guru-mapel.dashboard', []],
            ['Kepala Sekolah', 'dev.principal.dashboard', []],
            ['Admin', 'dev.preview', ['is_super_admin' => true]],
        ];

        foreach ($cases as $index => [$role, $route, $overrides]) {
            $account = $this->account($role, "role{$index}@sekolah.test", $overrides);
            $this->fakeGoogle("google-role-{$index}", $account->email);
            $this->get(route('auth.google.callback'))->assertRedirect(route($route));
            $this->post(route('logout'));
        }
    }

    public function test_unknown_inactive_unverified_and_non_super_admin_accounts_are_rejected(): void
    {
        $this->fakeGoogle('unknown', 'unknown@sekolah.test');
        $this->get(route('auth.google.callback'))->assertSessionHasErrors('google');

        $inactive = $this->account('GPK', 'inactive@sekolah.test', ['status_akun' => 'Nonaktif']);
        $this->fakeGoogle('inactive', $inactive->email);
        $this->get(route('auth.google.callback'))->assertSessionHasErrors('google');

        $unverified = $this->account('Guru Mapel', 'guru@sekolah.test');
        $this->fakeGoogle('unverified', $unverified->email, false);
        $this->get(route('auth.google.callback'))->assertSessionHasErrors('google');

        $admin = $this->account('Admin', 'admin@sekolah.test');
        $this->fakeGoogle('admin', $admin->email);
        $this->get(route('auth.google.callback'))->assertSessionHasErrors('google');

        $this->assertGuest('staff');
    }

    public function test_linked_account_cannot_be_taken_over_by_another_google_identity(): void
    {
        $account = $this->account('Tim Inklusi', 'tim@sekolah.test', ['google_id' => 'google-original']);
        $this->fakeGoogle('google-attacker', $account->email);

        $this->get(route('auth.google.callback'))->assertSessionHasErrors('google');
        $this->assertSame('google-original', $account->fresh()->google_id);
        $this->assertGuest('staff');
    }

    public function test_logout_clears_staff_and_parent_sessions(): void
    {
        $staff = $this->account('Kepala Sekolah', 'kepala@sekolah.test', ['google_id' => 'google-kepala']);
        $parent = $this->account('Orang Tua', 'orangtua@sekolah.test');

        $this->actingAs($staff, 'staff')->actingAs($parent, 'parent')
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest('staff');
        $this->assertGuest('parent');
    }
}
