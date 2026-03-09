<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'institution_code')) {
                try {
                    $table->dropIndex(['institution_code']);
                } catch (\Throwable $exception) {
                    // The index can be absent in some environments.
                }

                $table->dropColumn('institution_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'institution_code')) {
                $table->string('institution_code', 100)->nullable()->after('auth_subject');
                $table->index('institution_code');
            }
        });
    }
};
