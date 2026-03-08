<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['soporte', 'directivo', 'docente'])->default('docente')->after('password');
            $table->string('phone', 20)->nullable()->after('role');
            $table->string('identification_number', 30)->nullable()->after('phone');
            $table->string('avatar_path', 255)->nullable()->after('identification_number');
            $table->boolean('is_active')->default(true)->after('identification_number');

            // Index for role-based queries
            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'phone', 'identification_number', 'avatar_path', 'is_active']);
        });
    }
};
