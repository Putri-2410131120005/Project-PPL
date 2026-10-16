<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder hanya master role, tanpa membuat akun dummy di tabel users bawaan.
        $this->call(RoleSeeder::class);
    }
}
