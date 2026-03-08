<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('type', 32)->default('string');
            $table->json('value_json')->nullable();
            $table->text('value_text')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['institution_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_settings');
    }
};
