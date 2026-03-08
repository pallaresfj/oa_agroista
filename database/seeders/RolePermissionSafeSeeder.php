<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSafeSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roleNames = [
            'super_admin',
            'Soporte',
            'Directivo',
            'Centro',
            'Area',
            'Docente',
        ];

        foreach ($roleNames as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $panelAccess = Permission::query()->firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $roleNames)
            ->get()
            ->each(function (Role $role) use ($panelAccess): void {
                if (! $role->hasPermissionTo($panelAccess)) {
                    $role->givePermissionTo($panelAccess);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
