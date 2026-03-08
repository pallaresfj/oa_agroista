<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('role_has_permissions')
            || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $roles = ['super_admin', 'soporte', 'directivo', 'docente'];

        foreach ($roles as $name) {
            Role::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $panelPermission = Permission::query()->firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $roles)
            ->get()
            ->each(function (Role $role) use ($panelPermission): void {
                if (! $role->hasPermissionTo($panelPermission)) {
                    $role->givePermissionTo($panelPermission);
                }
            });

        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['soporte', 'directivo', 'docente'])
            ->pluck('id', 'name');

        DB::table('users')
            ->whereNotNull('role')
            ->whereIn('role', ['soporte', 'directivo', 'docente'])
            ->orderBy('id')
            ->chunk(250, function ($users) use ($roleIds): void {
                foreach ($users as $user) {
                    $roleId = $roleIds[$user->role] ?? null;

                    if (! $roleId) {
                        continue;
                    }

                    DB::table('model_has_roles')->updateOrInsert([
                        'role_id' => $roleId,
                        'model_type' => \App\Models\User::class,
                        'model_id' => $user->id,
                    ], []);
                }
            });
    }

    public function down(): void
    {
        // No-op: this migration only backfills role assignments from existing data.
    }
};
