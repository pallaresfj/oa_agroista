<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_app_access', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_app_access', 'ecosystem_app_id')) {
                $table->foreignId('ecosystem_app_id')->nullable()->after('user_id')->constrained('ecosystem_apps')->nullOnDelete();
                $table->index(['user_id', 'ecosystem_app_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_app_access', function (Blueprint $table): void {
            if (Schema::hasColumn('user_app_access', 'ecosystem_app_id')) {
                $table->dropConstrainedForeignId('ecosystem_app_id');
            }
        });
    }
};
