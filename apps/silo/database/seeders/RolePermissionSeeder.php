<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const RESOURCE_ACTIONS = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'delete_any',
        'restore',
        'restore_any',
        'force_delete',
        'force_delete_any',
        'replicate',
        'reorder',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensureExpectedPermissions();

        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name');

        Role::query()
            ->firstOrCreate([
                'name' => User::ROLE_SUPER_ADMIN,
                'guard_name' => 'web',
            ])
            ->syncPermissions($allPermissions);

        Role::query()
            ->firstOrCreate([
                'name' => User::ROLE_SOPORTE,
                'guard_name' => 'web',
            ])
            ->syncPermissions($allPermissions);

        Role::query()
            ->firstOrCreate([
                'name' => User::ROLE_DIRECTIVO,
                'guard_name' => 'web',
            ])
            ->syncPermissions(
                Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $this->directivoPermissions())
                    ->pluck('name'),
            );

        Role::query()
            ->firstOrCreate([
                'name' => User::ROLE_ADMINISTRATIVO,
                'guard_name' => 'web',
            ])
            ->syncPermissions(
                Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $this->administrativoPermissions())
                    ->pluck('name'),
            );

        Role::query()
            ->firstOrCreate([
                'name' => User::ROLE_DOCENTE,
                'guard_name' => 'web',
            ])
            ->syncPermissions(
                Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $this->docentePermissions())
                    ->pluck('name'),
            );

        Role::query()
            ->firstOrCreate([
                'name' => User::ROLE_VISITANTE,
                'guard_name' => 'web',
            ])
            ->syncPermissions(
                Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $this->visitantePermissions())
                    ->pluck('name'),
            );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensureExpectedPermissions(): void
    {
        $permissions = array_unique(array_merge(
            ['panel_user', 'view_home_dashboard', 'view_all_document_states', 'manage_drive_tools'],
            $this->resourcePermissions('document'),
            $this->resourcePermissions('document_category'),
            $this->resourcePermissions('entity'),
            $this->resourcePermissions('user'),
            $this->resourcePermissions('role'),
        ));

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function directivoPermissions(): array
    {
        return array_values(array_unique(array_merge(
            ['panel_user', 'view_home_dashboard', 'view_all_document_states', 'manage_drive_tools'],
            $this->resourcePermissions('document'),
            $this->resourcePermissions('document_category'),
            $this->resourcePermissions('entity'),
        )));
    }

    /**
     * @return list<string>
     */
    private function docentePermissions(): array
    {
        return [
            'panel_user',
            'view_home_dashboard',
            'view_any_document',
            'view_document',
        ];
    }

    /**
     * @return list<string>
     */
    private function administrativoPermissions(): array
    {
        return [
            'panel_user',
            'view_home_dashboard',
            'view_any_document',
            'view_document',
            'create_document',
            'update_document',
            'view_any_document_category',
            'view_document_category',
            'view_any_entity',
            'view_entity',
        ];
    }

    /**
     * @return list<string>
     */
    private function visitantePermissions(): array
    {
        return [
            'panel_user',
            'view_home_dashboard',
            'view_any_document',
            'view_document',
        ];
    }

    /**
     * @return list<string>
     */
    private function resourcePermissions(string $subject): array
    {
        return array_map(
            static fn (string $action): string => "{$action}_{$subject}",
            self::RESOURCE_ACTIONS,
        );
    }
}
