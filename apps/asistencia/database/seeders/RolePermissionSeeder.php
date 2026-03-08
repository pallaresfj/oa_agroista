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

        $directivoPermissions = [
            'panel_user',
            'view_directivo_dashboard',
            'view_today_schedule_widget',
            'view_attendance_scanner_widget',
            'view_personal_stats_widget',
            'view_attendance_calendar_widget',
            'view_global_stats_widget',
            'view_campus_summary_widget',
            'view_absent_users_widget',
            'view_user_summary_table_widget',
            'view_any_attendance',
            'view_attendance',
            'create_attendance',
            'update_attendance',
        ];

        Role::query()
            ->firstOrCreate([
                'name' => 'directivo',
                'guard_name' => 'web',
            ])
            ->syncPermissions(
                Permission::query()
                    ->whereIn('name', $directivoPermissions)
                    ->pluck('name'),
            );

        $docentePermissions = [
            'panel_user',
            'view_docente_dashboard',
            'view_today_schedule_widget',
            'view_attendance_scanner_widget',
            'view_personal_stats_widget',
            'view_attendance_calendar_widget',
            'view_any_attendance',
            'view_attendance',
            'create_attendance',
        ];

        Role::query()
            ->firstOrCreate([
                'name' => 'docente',
                'guard_name' => 'web',
            ])
            ->syncPermissions(
                Permission::query()
                    ->whereIn('name', $docentePermissions)
                    ->pluck('name'),
            );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
