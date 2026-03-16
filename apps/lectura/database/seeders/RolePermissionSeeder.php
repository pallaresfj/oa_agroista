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

        $allPermissions = Permission::query()->pluck('name')->values();

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

        $docentePermissions = $allPermissions
            ->intersect([
                'panel_user',
                'view_docente_dashboard',
                'view_reading_session',
                'view_reading_stats_widget',
                'view_recent_attempts_widget',
                'view_student',
                'view_reading_passage',
                'view_reading_attempt',
                'create_reading_attempt',
                'update_reading_attempt',
                'delete_reading_attempt',
            ])
            ->values();

        Role::query()
            ->firstOrCreate([
                'name' => 'docente',
                'guard_name' => 'web',
            ])
            ->syncPermissions($docentePermissions);

        $directivoPermissions = $allPermissions
            ->intersect([
                'panel_user',
                'view_docente_dashboard',
                'view_reading_stats_widget',
                'view_recent_attempts_widget',
                'view_any_reading_attempt',
                'view_reading_attempt',
                'view_any_student',
                'view_student',
                'view_any_reading_passage',
                'view_reading_passage',
            ])
            ->values();

        Role::query()
            ->firstOrCreate([
                'name' => 'directivo',
                'guard_name' => 'web',
            ])
            ->syncPermissions($directivoPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
