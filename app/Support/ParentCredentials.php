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
        return 'ortu_'.$student->nis;
    }

    public static function temporaryPassword(Siswa $student): string
    {
        $firstName = Str::of($student->nama_siswa)->squish()->before(' ')->value();

        return $firstName.$student->nis;
    }

    public static function visibleTemporaryPassword(Pengguna $parent): ?string
    {
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
