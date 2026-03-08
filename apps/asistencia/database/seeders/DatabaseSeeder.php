<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'pallaresfj@asyservicios.com',
            'email_verified_at' => now(),
            'password' => Hash::make('pass1234'),
            'role' => UserRole::SOPORTE,
            'is_active' => true,
        ]);

        $this->call([
            SettingsSeeder::class,
        ]);
    }
}
