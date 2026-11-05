<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\Siswa;
use Illuminate\Support\Facades\Auth;

class StudentPhotoAccess
{
    public function canView(Siswa $student): bool
    {
        foreach (['staff', 'guru_mapel', 'parent'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user instanceof Pengguna && $this->canViewFor($user, $student, $guard)) {
                return true;
            }
        }

        return false;
    }

    private function canViewFor(Pengguna $user, Siswa $student, string $guard): bool
    {
        if ($user->status_akun !== 'Aktif') {
            return false;
        }

        $role = $user->role?->nama_role;
        if ($guard === 'parent') {
            return $role === 'Orang Tua'
                && $user->orangTua?->relasiSiswa()->whereKey($student->getKey())->exists();
        }

        if ($role === 'Admin' || $role === 'Admin / Pengelola') {
            return (bool) $user->is_super_admin;
        }

        if ($role === 'Kepala Sekolah') {
            return true;
        }

        if (in_array($role, ['GPK', 'Tim Inklusi'], true)) {
            return $user->relasiPendampingSiswa()->where('id_siswa', $student->getKey())->exists();
        }

        if ($role === 'Guru Mapel') {
            return app(GuruMapelAccess::class)->students($user)->whereKey($student->getKey())->exists();
        }

        return false;
    }
}
