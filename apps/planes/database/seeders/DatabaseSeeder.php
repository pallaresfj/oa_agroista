<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(\Database\Seeders\RoleSeeder::class);
        $this->call(\Database\Seeders\PanelAccessSeeder::class);

        $user = User::factory()->create([
            'name' => 'Super Administrador',
            'email' => 'admin@asyservicios.com',
            'password' => bcrypt('7052'),
        ]);

        $user->assignRole('super_admin');

        $user = User::factory()->create([
            'name' => 'Soporte Técnico',
            'email' => 'pallaresfj@iedagropivijay.edu.co',
            'password' => bcrypt('7052'),
        ]);

        $user->assignRole('Soporte');

        $user = User::factory()->create([
            'name' => 'Francisco Pallares',
            'email' => 'rectoria@iedagropivijay.edu.co',
            'password' => bcrypt('7052'),
        ]);

        $user->assignRole('Directivo');

        $user = User::factory()->create([
            'name' => 'Lider Centro',
            'email' => 'centro@iedagropivijay.edu.co',
            'password' => bcrypt('7052'),
        ]);

        $user->assignRole('Centro');

        $user = User::factory()->create([
            'name' => 'Jefe Area',
            'email' => 'area@iedagropivijay.edu.co',
            'password' => bcrypt('7052'),
        ]);

        $user->assignRole('Area');

        $user = User::factory()->create([
            'name' => 'Docente',
            'email' => 'docente@iedagropivijay.edu.co',
            'password' => bcrypt('7052'),
        ]);

        $user->assignRole('Docente');
    }
}
