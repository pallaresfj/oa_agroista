<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }

        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');

        if (Schema::hasTable('roles')) {
            $this->dropLegacyIndex('roles', 'roles_slug_unique');

            Schema::table('roles', function (Blueprint $table): void {
                if (Schema::hasColumn('roles', 'slug')) {
                    $table->dropColumn('slug');
                }

                if (Schema::hasColumn('roles', 'is_system')) {
                    $table->dropColumn('is_system');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            $this->dropLegacyIndex('permissions', 'permissions_code_unique');

            Schema::table('permissions', function (Blueprint $table): void {
                if (Schema::hasColumn('permissions', 'code')) {
                    $table->dropColumn('code');
                }

                if (Schema::hasColumn('permissions', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }

    protected function dropLegacyIndex(string $table, string $index): void
    {
        try {
            DB::statement("DROP INDEX {$index}");
        } catch (\Throwable) {
            // Ignore when the index does not exist for the current engine.
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropUnique($index);
            });
        } catch (\Throwable) {
            // Ignore when the index has already been removed.
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role')->default('docente')->after('password');
            });
        }

        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table): void {
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['role_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();
                $table->primary(['permission_id', 'role_id']);
                $table->index('permission_id');
                $table->index('role_id');
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table): void {
                if (! Schema::hasColumn('roles', 'slug')) {
                    $table->string('slug')->nullable()->after('id');
                }

                if (! Schema::hasColumn('roles', 'is_system')) {
                    $table->boolean('is_system')->default(false)->after('name');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table): void {
                if (! Schema::hasColumn('permissions', 'code')) {
                    $table->string('code')->nullable()->after('id');
                }

                if (! Schema::hasColumn('permissions', 'description')) {
                    $table->string('description')->nullable()->after('code');
                }
            });
        }
    }
};
