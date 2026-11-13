<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LocalAddressSeeder::class,
            AcademicProgramSeeder::class,
            RoleAndUserSeeder::class,
            SponsorshipProgramSeeder::class,
        ]);

        // Institutional registry imports are excluded from general/container seeding.
        // Phase B1 provides only sponsorflow:registry-preflight; it cannot persist data.
    }
}
