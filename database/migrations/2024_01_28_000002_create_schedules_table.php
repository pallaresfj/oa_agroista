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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0 = Sunday, 6 = Saturday
            $table->time('check_in_time');
            $table->time('check_out_time')->nullable();
            $table->integer('tolerance_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for quick lookups
            $table->index(['user_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
