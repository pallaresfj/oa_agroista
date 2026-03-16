<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'super_admin',
            'soporte',
            'directivo',
            'docente',
        ];

        foreach ($roles as $role) {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $legacyRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'estudiante')
            ->first();

        if ($legacyRole) {
            DB::table('model_has_roles')->where('role_id', $legacyRole->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $legacyRole->id)->delete();
            $legacyRole->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
