<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Eliminar registros sin romper claves foráneas
        Role::query()->where('guard_name', 'web')->delete();

        // Reiniciar IDs solo en motores compatibles con AUTO_INCREMENT.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE roles AUTO_INCREMENT = 1');
        }

        // Inserta roles con IDs definidos
        Role::create([
            'id' => 1,
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'id' => 2,
            'name' => 'Soporte',
            'guard_name' => 'web',
        ]);

        Role::create([
            'id' => 3,
            'name' => 'Directivo',
            'guard_name' => 'web',
        ]);

        Role::create([
            'id' => 4,
            'name' => 'Centro',
            'guard_name' => 'web',
        ]);

        Role::create([
            'id' => 5,
            'name' => 'Area',
            'guard_name' => 'web',
        ]);

        Role::create([
            'id' => 6,
            'name' => 'Docente',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Si usas Shield con tenancy, puedes añadir team_id si aplica
    }
}
