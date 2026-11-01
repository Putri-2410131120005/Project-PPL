<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Only stable reference data belongs in the default seeder. Operational
        // records must be created through the application workflow.
        $this->call(RoleSeeder::class);
    }
}
