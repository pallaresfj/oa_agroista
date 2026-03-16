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

        Role::query()
            ->firstOrCreate([
                'name' => 'soporte',
                'guard_name' => 'web',
            ])
            ->syncPermissions($allPermissions);

        $docentePermissions = Permission::query()
            ->whereNotIn('name', [
                'view_any_role',
                'view_role',
                'create_role',
                'update_role',
                'delete_role',
                'delete_any_role',
                'view_any_user',
                'view_user',
                'create_user',
                'update_user',
                'delete_user',
                'delete_any_user',
                'view_any_course',
                'view_course',
                'create_course',
                'update_course',
                'delete_course',
                'delete_any_course',
            ])
            ->pluck('name');

        Role::query()
            ->firstOrCreate([
                'name' => 'docente',
                'guard_name' => 'web',
            ])
            ->syncPermissions($docentePermissions);

        $directivoPermissions = Permission::query()
            ->where(function ($query): void {
                $query->where('name', 'panel_user')
                    ->orWhere('name', 'view_docente_dashboard')
                    ->orWhere('name', 'view_reading_stats_widget')
                    ->orWhere('name', 'view_recent_attempts_widget')
                    ->orWhere('name', 'view_any_reading_attempt')
                    ->orWhere('name', 'view_reading_attempt')
                    ->orWhere('name', 'view_any_student')
                    ->orWhere('name', 'view_student')
                    ->orWhere('name', 'view_any_reading_passage')
                    ->orWhere('name', 'view_reading_passage');
            })
            ->pluck('name');

        Role::query()
            ->firstOrCreate([
                'name' => 'directivo',
                'guard_name' => 'web',
            ])
            ->syncPermissions($directivoPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
