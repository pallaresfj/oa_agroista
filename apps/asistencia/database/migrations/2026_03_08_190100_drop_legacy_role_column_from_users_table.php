<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
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

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['soporte', 'directivo', 'docente'])->default('docente')->after('password');
            $table->index('role');
        });
    }
};
