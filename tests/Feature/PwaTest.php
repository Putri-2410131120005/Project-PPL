<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_fallback_is_available(): void
    {
        $this->get(route('offline'))
            ->assertOk()
            ->assertSee('Anda sedang offline')
            ->assertSee('Data siswa dan catatan tidak disimpan di perangkat');
    }

    public function test_login_page_exposes_pwa_installation_metadata(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('pwa-service-worker', false)
            ->assertSee('apple-touch-icon', false);
    }

    public function test_public_vapid_key_can_be_requested_by_the_pwa(): void
    {
        config()->set('push.vapid.publicKey', 'test-public-key');

        $this->getJson(route('push.vapid-key'))
            ->assertOk()
            ->assertJsonPath('publicKey', 'test-public-key');
    }

    public function test_authenticated_staff_can_register_a_push_subscription(): void
    {
        $role = Role::query()->create(['nama_role' => 'GPK']);
        $gpk = Pengguna::query()->create([
            'id_role' => $role->getKey(),
            'nama' => 'GPK Push',
            'email' => 'gpk-push@example.test',
            'password' => bcrypt('password-testing'),
            'status_akun' => 'Aktif',
        ]);

        $gpk->forceFill(['google_id' => 'google-gpk-push'])->save();
        $this->actingAs($gpk, 'staff')
            ->postJson(route('push.subscriptions.store'), [
                'endpoint' => 'https://push.example.test/subscription/one',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
            ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', ['id_pengguna' => $gpk->getKey()]);
    }

    public function test_principal_subscription_is_not_assigned_to_a_stale_teacher_guard(): void
    {
        $teacherRole = Role::query()->create(['nama_role' => 'Guru Mapel']);
        $principalRole = Role::query()->create(['nama_role' => 'Kepala Sekolah']);
        $teacher = Pengguna::query()->create([
            'id_role' => $teacherRole->getKey(), 'nama' => 'Guru Lama',
            'email' => 'guru-lama@example.test', 'password' => bcrypt('password-testing'), 'status_akun' => 'Aktif',
        ]);
        $principal = Pengguna::query()->create([
            'id_role' => $principalRole->getKey(), 'nama' => 'Kepala Sekolah Push',
            'email' => 'kepala-push@example.test', 'password' => bcrypt('password-testing'), 'status_akun' => 'Aktif',
        ]);

        $principal->forceFill(['google_id' => 'google-principal-push'])->save();
        $this->actingAs($teacher, 'guru_mapel')->actingAs($principal, 'staff')
            ->postJson(route('push.subscriptions.store'), [
                'endpoint' => 'https://push.example.test/subscription/principal',
                'keys' => ['p256dh' => 'principal-public-key', 'auth' => 'principal-auth-token'],
            ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'id_pengguna' => $principal->getKey(),
            'endpoint_hash' => hash('sha256', 'https://push.example.test/subscription/principal'),
        ]);
        $this->assertDatabaseMissing('push_subscriptions', [
            'id_pengguna' => $teacher->getKey(),
            'endpoint_hash' => hash('sha256', 'https://push.example.test/subscription/principal'),
        ]);
    }
}
