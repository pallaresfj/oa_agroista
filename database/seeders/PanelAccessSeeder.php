<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PanelAccessSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::query()->firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web',
        ]);

        $roleNames = [
            'super_admin',
            'Soporte',
            'Directivo',
            'Centro',
            'Area',
            'Docente',
        ];

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $roleNames)
            ->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
