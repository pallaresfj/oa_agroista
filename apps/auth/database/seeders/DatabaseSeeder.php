<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InstitutionSeeder::class,
            SuperAdminsSeeder::class,
            OAuthClientsSeeder::class,
            EcosystemAppsSeeder::class,
        ]);
    }
}
