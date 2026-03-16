<?php

namespace Database\Seeders;

use App\Models\ReadingPassage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
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
        ]);

        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'app',
            '--option' => 'permissions',
        ]);

        $this->call([
            RolePermissionSeeder::class,
        ]);

        $users = [
            [
                'name' => 'Usuario Soporte Lectura',
                'email' => env('LECTURA_ADMIN_EMAIL', 'pallaresfj@iedagropivijay.edu.co'),
                'role' => User::ROLE_SOPORTE,
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

        ReadingPassage::query()->updateOrCreate(
            ['title' => 'La mariposa y el jardín'],
            [
                'difficulty_level' => 'Básico',
                'content' => 'La mariposa voló por el jardín durante la mañana. Se posó sobre una flor amarilla y luego siguió su camino entre las hojas verdes y el canto de los pájaros.',
                'is_active' => true,
            ],
        );

        ReadingPassage::query()->updateOrCreate(
            ['title' => 'El río de la montaña'],
            [
                'difficulty_level' => 'Intermedio',
                'content' => 'Desde la parte alta de la montaña bajaba un río de agua fría y transparente. Los habitantes del pueblo cuidaban sus orillas porque sabían que el agua daba vida a sus cultivos y a sus animales.',
                'is_active' => true,
            ],
        );
    }
}
