<?php

namespace App\Support;

use App\Models\Pengguna;
use App\Models\Siswa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParentCredentials
{
    public static function username(Siswa $student): string
    {
        // Username orang tua diturunkan dari NIS siswa yang terhubung.
        return 'ortu_'.$student->nis;
    }

    public static function temporaryPassword(Siswa $student): string
    {
        // Password awal memakai nama depan dan NIS agar mudah diberikan oleh admin.
        $firstName = Str::of($student->nama_siswa)->squish()->before(' ')->value();

        return $firstName.$student->nis;
    }

    public static function visibleTemporaryPassword(Pengguna $parent): ?string
    {
        // Password sementara hanya ditampilkan selama akun masih wajib menggantinya.
        if (! $parent->wajib_ganti_password) {
            return null;
        }

        $student = self::linkedStudent($parent);

        if (! $student) {
            return null;
        }

        $candidate = self::temporaryPassword($student);

        return Hash::check($candidate, $parent->password) ? $candidate : null;
    }

    public static function linkedStudent(Pengguna $parent): ?Siswa
    {
        // Menentukan siswa dari relasi akun, dengan fallback saat hanya ada satu anak.
        $relations = $parent->orangTua?->relasiSiswa;

        if (! $relations) {
            return null;
        }

        $matchingRelation = $relations->first(fn ($relation) => in_array($parent->username, [
            $relation->siswa?->nis,
            $relation->siswa ? self::username($relation->siswa) : null,
        ], true));

        return $matchingRelation?->siswa ?? ($relations->count() === 1 ? $relations->first()?->siswa : null);
    }
}
