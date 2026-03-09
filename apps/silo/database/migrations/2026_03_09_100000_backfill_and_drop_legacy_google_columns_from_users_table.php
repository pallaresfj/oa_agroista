<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'google_subject') && Schema::hasColumn('users', 'auth_subject')) {
            DB::table('users')
                ->whereNotNull('google_subject')
                ->where(function ($query): void {
                    $query->whereNull('auth_subject')
                        ->orWhere('auth_subject', '');
                })
                ->update([
                    'auth_subject' => DB::raw('google_subject'),
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasColumn('users', 'last_google_login_at') && Schema::hasColumn('users', 'last_sso_login_at')) {
            DB::table('users')
                ->whereNotNull('last_google_login_at')
                ->whereNull('last_sso_login_at')
                ->update([
                    'last_sso_login_at' => DB::raw('last_google_login_at'),
                    'updated_at' => now(),
                ]);
        }

        $this->dropGoogleSubjectUniqueIndex();

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'google_subject')) {
                $table->dropColumn('google_subject');
            }

            if (Schema::hasColumn('users', 'last_google_login_at')) {
                $table->dropColumn('last_google_login_at');
            }

            if (Schema::hasColumn('users', 'avatar_url')) {
                $table->dropColumn('avatar_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'google_subject')) {
                $table->string('google_subject')->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'last_google_login_at')) {
                $table->timestamp('last_google_login_at')->nullable()->after('email_verified_at');
            }

            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('password');
            }
        });

        if (Schema::hasColumn('users', 'auth_subject') && Schema::hasColumn('users', 'google_subject')) {
            DB::table('users')
                ->whereNotNull('auth_subject')
                ->where(function ($query): void {
                    $query->whereNull('google_subject')
                        ->orWhere('google_subject', '');
                })
                ->update([
                    'google_subject' => DB::raw('auth_subject'),
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasColumn('users', 'last_sso_login_at') && Schema::hasColumn('users', 'last_google_login_at')) {
            DB::table('users')
                ->whereNotNull('last_sso_login_at')
                ->whereNull('last_google_login_at')
                ->update([
                    'last_google_login_at' => DB::raw('last_sso_login_at'),
                    'updated_at' => now(),
                ]);
        }
    }

    private function dropGoogleSubjectUniqueIndex(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'google_subject')) {
            return;
        }

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('DROP INDEX users_google_subject_unique ON users');

                return;
            }

            DB::statement('DROP INDEX IF EXISTS users_google_subject_unique');
        } catch (\Throwable) {
            // Ignore when the index does not exist.
        }
    }
};
