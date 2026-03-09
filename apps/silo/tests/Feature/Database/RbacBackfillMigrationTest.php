<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('backfills canonical model_has_roles from legacy role sources', function () {
    if (! Schema::hasColumn('users', 'role')) {
        Schema::table('users', function ($table): void {
            $table->string('role')->nullable()->after('password');
        });
    }

    if (! Schema::hasColumn('roles', 'slug')) {
        Schema::table('roles', function ($table): void {
            $table->string('slug')->nullable()->after('id');
        });
    }

    if (! Schema::hasColumn('roles', 'is_system')) {
        Schema::table('roles', function ($table): void {
            $table->boolean('is_system')->default(false)->after('name');
        });
    }

    if (! Schema::hasColumn('permissions', 'code')) {
        Schema::table('permissions', function ($table): void {
            $table->string('code')->nullable()->after('id');
        });
    }

    if (! Schema::hasColumn('permissions', 'description')) {
        Schema::table('permissions', function ($table): void {
            $table->string('description')->nullable()->after('code');
        });
    }

    if (! Schema::hasTable('role_user')) {
        Schema::create('role_user', function ($table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });
    }

    if (! Schema::hasTable('permission_role')) {
        Schema::create('permission_role', function ($table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
            $table->primary(['permission_id', 'role_id']);
        });
    }

    DB::table('model_has_roles')->truncate();
    DB::table('role_has_permissions')->truncate();
    DB::table('model_has_permissions')->truncate();
    DB::table('role_user')->truncate();
    DB::table('permission_role')->truncate();
    DB::table('permissions')->truncate();
    DB::table('roles')->truncate();
    DB::table('users')->truncate();

    DB::table('users')->insert([
        [
            'id' => 10,
            'name' => 'Administrador Legacy',
            'email' => 'legacy-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 11,
            'name' => 'Rector Legacy',
            'email' => 'legacy-rector@example.com',
            'password' => bcrypt('password'),
            'role' => 'rector',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 12,
            'name' => 'Docente Legacy',
            'email' => 'legacy-docente@example.com',
            'password' => bcrypt('password'),
            'role' => 'docente',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 13,
            'name' => 'Role Pivot Legacy',
            'email' => 'legacy-pivot@example.com',
            'password' => bcrypt('password'),
            'role' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('roles')->insert([
        [
            'id' => 1,
            'slug' => 'rector',
            'name' => 'rector',
            'guard_name' => 'web',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'slug' => 'administrador',
            'name' => 'administrador',
            'guard_name' => 'web',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 3,
            'slug' => 'editor',
            'name' => 'editor',
            'guard_name' => 'web',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 4,
            'slug' => 'lector',
            'name' => 'lector',
            'guard_name' => 'web',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('permissions')->insert([
        [
            'id' => 1,
            'name' => 'documents.view',
            'guard_name' => 'web',
            'code' => 'documents.view',
            'description' => 'Ver documentos',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('role_user')->insert([
        'role_id' => 3,
        'user_id' => 13,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('permission_role')->insert([
        'permission_id' => 1,
        'role_id' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_03_08_200000_prepare_shield_permission_schema.php');
    $migration->up();

    $canonicalRoleIds = DB::table('roles')
        ->whereIn('name', ['super_admin', 'soporte', 'directivo', 'docente', 'administrativo', 'visitante'])
        ->pluck('id', 'name');

    expect($canonicalRoleIds)->toHaveKeys(['super_admin', 'soporte', 'directivo', 'docente', 'administrativo', 'visitante']);

    $assignedRoleNamesByUser = DB::table('model_has_roles')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('model_type', App\Models\User::class)
        ->whereIn('model_id', [10, 11, 12, 13])
        ->select('model_has_roles.model_id', 'roles.name')
        ->get()
        ->groupBy('model_id')
        ->map(fn ($rows) => collect($rows)->pluck('name')->all());

    expect($assignedRoleNamesByUser[10] ?? [])->toContain('administrativo');
    expect($assignedRoleNamesByUser[11] ?? [])->toContain('directivo');
    expect($assignedRoleNamesByUser[12] ?? [])->toContain('docente');
    expect($assignedRoleNamesByUser[13] ?? [])->toContain('directivo');

    expect(DB::table('role_has_permissions')
        ->where('role_id', 3)
        ->where('permission_id', 1)
        ->exists())
        ->toBeTrue();
});
