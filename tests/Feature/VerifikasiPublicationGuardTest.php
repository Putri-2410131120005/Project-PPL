<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Role;
use App\Models\VerifikasiCatatan;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiPublicationGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_active_gpk_can_create_a_consistent_publication(): void
    {
        $gpk = $this->user('GPK', 'Aktif');

        $verification = $this->verification()
            ->authorizePublicationMutationBy($gpk)
            ->forceFill([
                'id_publikator' => $gpk->getKey(),
                'tanggal_publikasi' => now(),
            ]);

        $this->assertTrue($verification->save());
        $this->assertDatabaseHas('verifikasi_catatan', [
            'id_verifikasi' => $verification->getKey(),
            'id_publikator' => $gpk->getKey(),
            'status_verifikasi' => 'Terverifikasi',
        ]);
    }

    public function test_publication_requires_publisher_date_and_verified_status(): void
    {
        $gpk = $this->user('GPK', 'Aktif');

        foreach ([
            ['id_publikator' => $gpk->getKey(), 'tanggal_publikasi' => null, 'status_verifikasi' => 'Terverifikasi'],
            ['id_publikator' => null, 'tanggal_publikasi' => now(), 'status_verifikasi' => 'Terverifikasi'],
            ['id_publikator' => $gpk->getKey(), 'tanggal_publikasi' => now(), 'status_verifikasi' => 'Perlu Revisi'],
        ] as $attributes) {
            try {
                $this->verification()->authorizePublicationMutationBy($gpk)->forceFill($attributes)->save();
                $this->fail('Kombinasi publikasi yang tidak valid seharusnya ditolak.');
            } catch (DomainException) {
                $this->assertDatabaseCount('verifikasi_catatan', 0);
            }
        }
    }

    public function test_publication_cannot_bypass_authorization_with_force_fill(): void
    {
        $gpk = $this->user('GPK', 'Aktif');

        $this->expectException(DomainException::class);

        $this->verification()->forceFill([
            'id_publikator' => $gpk->getKey(),
            'tanggal_publikasi' => now(),
        ])->save();
    }

    public function test_inactive_or_non_gpk_actor_cannot_publish(): void
    {
        $publisher = $this->user('GPK', 'Aktif');

        foreach ([$this->user('Guru Mapel', 'Aktif'), $this->user('GPK', 'Nonaktif')] as $actor) {
            try {
                $this->verification()->authorizePublicationMutationBy($actor)->forceFill([
                    'id_publikator' => $publisher->getKey(),
                    'tanggal_publikasi' => now(),
                ])->save();
                $this->fail('Aktor tanpa otorisasi GPK aktif seharusnya ditolak.');
            } catch (DomainException) {
                $this->assertDatabaseCount('verifikasi_catatan', 0);
            }
        }
    }

    public function test_existing_publication_cannot_be_changed_without_authorization(): void
    {
        $gpk = $this->user('GPK', 'Aktif');
        $verification = $this->verification()->authorizePublicationMutationBy($gpk)->forceFill([
            'id_publikator' => $gpk->getKey(),
            'tanggal_publikasi' => now(),
        ]);
        $verification->save();

        $this->expectException(DomainException::class);

        $verification->forceFill(['id_publikator' => null, 'tanggal_publikasi' => null])->save();
    }

    private function verification(): VerifikasiCatatan
    {
        $gpk = Pengguna::query()->whereHas('role', fn ($query) => $query->where('nama_role', 'GPK'))->first()
            ?? $this->user('GPK', 'Aktif');

        return (new VerifikasiCatatan())->forceFill([
            'id_verifikator' => $gpk->getKey(),
            'versi_pengajuan' => 1,
            'status_verifikasi' => 'Terverifikasi',
            'tanggal_verifikasi' => now(),
        ]);
    }

    private function user(string $roleName, string $status): Pengguna
    {
        $role = Role::query()->firstOrCreate(['nama_role' => $roleName]);

        return Pengguna::query()->forceCreate([
            'id_role' => $role->getKey(),
            'nama' => $roleName.' '.$status.' '.uniqid(),
            'username' => strtolower(str_replace(' ', '-', $roleName)).'-'.uniqid(),
            'password' => 'testing-password',
            'status_akun' => $status,
        ]);
    }
}
