<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Guru Mapel',
            'Tim Inklusi',
            'GPK',
            'Kepala Sekolah',
            'Orang Tua',
            'Admin'
        ] as $namaRole) {
            DB::table('role')->updateOrInsert(
                ['nama_role' => $namaRole],
                ['nama_role' => $namaRole]
            );
        }
    }
}
