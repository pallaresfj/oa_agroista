<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->prepareRolesTable();
        $this->preparePermissionsTable();
        $this->createSpatiePivotTables();
        $this->ensureCanonicalRoles();
        $this->backfillModelHasRoles();
        $this->backfillRoleHasPermissions();
        $this->ensureUniqueIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
    }

    protected function prepareRolesTable(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });

        DB::table('roles')
            ->whereNull('guard_name')
            ->orWhere('guard_name', '')
            ->update(['guard_name' => 'web']);
    }

    protected function preparePermissionsTable(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        Schema::table('permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('permissions', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('permissions', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });

        if (Schema::hasColumn('permissions', 'code')) {
            DB::table('permissions')
                ->where(function ($query): void {
                    $query->whereNull('name')->orWhere('name', '');
                })
                ->update(['name' => DB::raw('code')]);
        }

        DB::table('permissions')
            ->whereNull('guard_name')
            ->orWhere('guard_name', '')
            ->update(['guard_name' => 'web']);

        DB::table('permissions')
            ->where(function ($query): void {
                $query->whereNull('name')->orWhere('name', '');
            })
            ->orderBy('id')
            ->chunk(250, function ($permissions): void {
                foreach ($permissions as $permission) {
                    DB::table('permissions')
                        ->where('id', $permission->id)
                        ->update(['name' => 'legacy_permission_' . $permission->id]);
                }
            });
    }

    protected function createSpatiePivotTables(): void
    {
        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign('permission_id')
                    ->references('id')
                    ->on('permissions')
                    ->onDelete('cascade');

                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');

                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');

                $table->foreign('permission_id')
                    ->references('id')
                    ->on('permissions')
                    ->onDelete('cascade');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');

                $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
            });
        }
    }

    protected function ensureCanonicalRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $hasSlug = Schema::hasColumn('roles', 'slug');
        $hasIsSystem = Schema::hasColumn('roles', 'is_system');
        $now = now();

        foreach (['super_admin', 'soporte', 'directivo', 'docente', 'administrativo', 'visitante'] as $roleName) {
            $existing = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                continue;
            }

            $payload = [
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasSlug) {
                $payload['slug'] = $roleName;
            }

            if ($hasIsSystem) {
                $payload['is_system'] = true;
            }

            DB::table('roles')->insert($payload);
        }
    }

    protected function backfillModelHasRoles(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', ['super_admin', 'soporte', 'directivo', 'docente', 'administrativo', 'visitante'])
            ->pluck('id', 'name');

        if ($roleIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->select('id', 'role')
                ->whereNotNull('role')
                ->orderBy('id')
                ->chunk(250, function ($users) use ($roleIds): void {
                    foreach ($users as $user) {
                        $canonicalRole = $this->normalizeLegacyRole((string) $user->role);

                        if (! isset($roleIds[$canonicalRole])) {
                            continue;
                        }

                        DB::table('model_has_roles')->updateOrInsert([
                            'role_id' => $roleIds[$canonicalRole],
                            'model_type' => \App\Models\User::class,
                            'model_id' => $user->id,
                        ], []);
                    }
                });
        }

        if (Schema::hasTable('role_user')) {
            $query = DB::table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->select('role_user.user_id', 'roles.name', 'roles.guard_name');

            if (Schema::hasColumn('roles', 'slug')) {
                $query->addSelect('roles.slug');
            }

            $query
                ->orderBy('role_user.user_id')
                ->chunk(250, function ($rows) use ($roleIds): void {
                    foreach ($rows as $row) {
                        $legacyValue = isset($row->slug) && is_string($row->slug) && trim($row->slug) !== ''
                            ? $row->slug
                            : (string) $row->name;

                        $canonicalRole = $this->normalizeLegacyRole($legacyValue);

                        if (! isset($roleIds[$canonicalRole])) {
                            continue;
                        }

                        DB::table('model_has_roles')->updateOrInsert([
                            'role_id' => $roleIds[$canonicalRole],
                            'model_type' => \App\Models\User::class,
                            'model_id' => $row->user_id,
                        ], []);
                    }
                });
        }
    }

    protected function backfillRoleHasPermissions(): void
    {
        if (! Schema::hasTable('permission_role') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        DB::table('permission_role')
            ->select('permission_id', 'role_id')
            ->orderBy('role_id')
            ->chunk(250, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('role_has_permissions')->updateOrInsert([
                        'permission_id' => $row->permission_id,
                        'role_id' => $row->role_id,
                    ], []);
                }
            });
    }

    protected function ensureUniqueIndexes(): void
    {
        try {
            DB::statement('CREATE UNIQUE INDEX roles_name_guard_name_unique ON roles (name, guard_name)');
        } catch (\Throwable) {
            // Ignore if index already exists.
        }

        try {
            DB::statement('CREATE UNIQUE INDEX permissions_name_guard_name_unique ON permissions (name, guard_name)');
        } catch (\Throwable) {
            // Ignore if index already exists.
        }
    }

    protected function normalizeLegacyRole(string $value): string
    {
        return match (mb_strtolower(trim($value))) {
            'super_admin' => 'super_admin',
            'soporte' => 'soporte',
            'rector', 'editor', 'directivo' => 'directivo',
            'administrador', 'administrativo' => 'administrativo',
            'visitante' => 'visitante',
            default => 'docente',
        };
    }
};
