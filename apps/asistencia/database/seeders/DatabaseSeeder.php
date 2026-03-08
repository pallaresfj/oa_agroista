<?php

namespace Database\Seeders;

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
        $this->call([
            RoleSeeder::class,
            PanelAccessSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $users = [
            [
                'name' => 'Soporte',
                'email' => 'pallaresfj@iedagropivijay.edu.co',
                'role' => User::ROLE_SOPORTE,
            ],
            [
                'name' => 'Directivo',
                'email' => 'rectoria@iedagropivijay.edu.co',
                'role' => User::ROLE_DIRECTIVO,
            ],
        ];

        foreach ($users as $seedUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $seedUser['email']],
                [
                    'name' => $seedUser['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('pass1234'),
                    'is_active' => true,
                ],
            );

            $user->syncRoles([$seedUser['role']]);
        }

        $this->call([
            SettingsSeeder::class,
        ]);
    }
}
