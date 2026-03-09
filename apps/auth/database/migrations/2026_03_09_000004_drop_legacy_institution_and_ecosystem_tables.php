<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_app_access') && Schema::hasColumn('user_app_access', 'ecosystem_app_id')) {
            Schema::table('user_app_access', function (Blueprint $table): void {
                try {
                    $table->dropForeign(['ecosystem_app_id']);
                } catch (Throwable) {
                    // Ignore if foreign key does not exist.
                }

                try {
                    $table->dropIndex(['user_id', 'ecosystem_app_id']);
                } catch (Throwable) {
                    // Ignore if index does not exist.
                }

                $table->dropColumn('ecosystem_app_id');
            });
        }

        Schema::dropIfExists('ecosystem_app_redirect_uris');
        Schema::dropIfExists('ecosystem_apps');
        Schema::dropIfExists('institutions');
    }

    public function down(): void
    {
        if (! Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('logo_url')->nullable();
                $table->string('primary_color', 20)->nullable();
                $table->string('secondary_color', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ecosystem_apps')) {
            Schema::create('ecosystem_apps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
                $table->string('slug');
                $table->string('name');
                $table->string('base_url');
                $table->string('oauth_client_id')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['institution_id', 'slug']);
            });
        }

        if (! Schema::hasTable('ecosystem_app_redirect_uris')) {
            Schema::create('ecosystem_app_redirect_uris', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ecosystem_app_id')->constrained()->cascadeOnDelete();
                $table->string('redirect_uri');
                $table->boolean('is_frontchannel_logout')->default(false);
                $table->timestamps();

                $table->unique(['ecosystem_app_id', 'redirect_uri'], 'app_redirect_uri_unique');
            });
        }

        if (Schema::hasTable('user_app_access') && ! Schema::hasColumn('user_app_access', 'ecosystem_app_id')) {
            Schema::table('user_app_access', function (Blueprint $table): void {
                $table->foreignId('ecosystem_app_id')->nullable()->after('user_id')->constrained('ecosystem_apps')->nullOnDelete();
                $table->index(['user_id', 'ecosystem_app_id']);
            });
        }
    }
};
