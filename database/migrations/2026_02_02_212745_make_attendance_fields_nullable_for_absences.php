<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration allows attendance records to be created without
     * check_in_time, latitude, and longitude for auto-generated ABSENT records.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('check_in_time')->nullable()->change();
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('check_in_time')->nullable(false)->change();
            $table->decimal('latitude', 10, 8)->nullable(false)->change();
            $table->decimal('longitude', 11, 8)->nullable(false)->change();
        });
    }
};
