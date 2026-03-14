<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::query()->pluck('name');

        Role::query()
            ->firstOrCreate([
                'name' => 'super_admin',
                'guard_name' => 'web',
            ])
            ->syncPermissions($allPermissions);

        $docentePermissions = Permission::query()
            ->whereNotIn('name', [
                'view_any_user',
                'view_user',
                'create_user',
                'update_user',
                'delete_user',
                'delete_any_user',
            ])
            ->pluck('name');

        Role::query()
            ->firstOrCreate([
                'name' => 'docente',
                'guard_name' => 'web',
            ])
            ->syncPermissions($docentePermissions);

        Role::query()
            ->firstOrCreate([
                'name' => 'estudiante',
                'guard_name' => 'web',
            ])
            ->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
