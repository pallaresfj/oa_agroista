<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EcosystemAppsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OAuthClientsSeeder::class,
        ]);
    }
}
