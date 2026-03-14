<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_passages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('difficulty_level', 50)->nullable();
            $table->longText('content');
            $table->unsignedInteger('word_count')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_passages');
    }
};
