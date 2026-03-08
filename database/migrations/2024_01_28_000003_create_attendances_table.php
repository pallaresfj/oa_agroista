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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date')->nullable();
            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('distance_from_campus', 8, 2)->nullable();
            $table->enum('status', ['on_time', 'late', 'absent', 'justified'])->default('on_time');
            $table->text('notes')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'check_in_time']);
            $table->index(['campus_id', 'check_in_time']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
