<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_errors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('reading_attempts')->cascadeOnDelete();
            $table->string('error_type', 30);
            $table->unsignedInteger('occurred_at_seconds')->default(0);
            $table->unsignedInteger('word_index')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['attempt_id', 'error_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_errors');
    }
};
