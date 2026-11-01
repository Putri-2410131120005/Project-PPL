<?php

namespace Tests;

use App\Models\Pengguna;
use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Compatibility for module tests written before the runtime development
     * role selector was removed. The session values have no application
     * authority; tests authenticate a real database account through its guard.
     */
    public function withSession(array $data)
    {
        parent::withSession($data);

        $slug = $data['dev_role'] ?? null;
        if (! is_string($slug)) {
            return $this;
        }

        $roleName = match ($slug) {
            'admin' => 'Admin / Pengelola',
            'gpk' => 'GPK',
            'tim-inklusi' => 'Tim Inklusi',
            'guru-mapel' => 'Guru Mapel',
            'kepala-sekolah' => 'Kepala Sekolah',
            'orang-tua' => 'Orang Tua',
            default => null,
        };
        if ($roleName === null) {
            return $this;
        }

        $account = isset($data['dev_pengguna_id'])
            ? Pengguna::query()->find($data['dev_pengguna_id'])
            : Pengguna::query()
                ->where('status_akun', 'Aktif')
                ->whereHas('role', fn ($query) => $query->where('nama_role', $roleName))
                ->when($slug === 'admin', fn ($query) => $query->where('is_super_admin', true))
                ->first();

        if (! $account) {
            $role = Role::query()->firstOrCreate(['nama_role' => $roleName]);
            $account = Pengguna::query()->create([
                'id_role' => $role->getKey(),
                'nama' => 'Akun Autentikasi Test '.$roleName,
                'username' => 'auth-test-'.$slug,
                'email' => 'auth-test-'.$slug.'@example.test',
                'google_id' => 'google-test-'.$slug,
                'password' => 'testing-password',
                'status_akun' => 'Aktif',
            ]);
            if ($slug === 'admin') {
                $account->forceFill(['is_super_admin' => true])->save();
            }
        } elseif ($slug !== 'orang-tua') {
            $account->forceFill(['google_id' => $account->google_id ?: 'google-test-'.$slug.'-'.$account->getKey()])->save();
        }

        if ($slug === 'orang-tua') {
            return $this->actingAs($account, 'parent');
        }

        $this->actingAs($account, 'staff');
        if ($slug === 'guru-mapel') {
            $this->actingAs($account, 'guru_mapel');
        }

        return $this;
    }
}
