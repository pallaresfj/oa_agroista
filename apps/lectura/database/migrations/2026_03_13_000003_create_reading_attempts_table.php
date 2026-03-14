<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('passage_id')->constrained('reading_passages')->cascadeOnDelete();
            $table->string('status', 20)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('word_count')->default(0);
            $table->decimal('words_per_minute', 8, 2)->default(0);
            $table->unsignedInteger('total_errors')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_attempts');
    }
};
