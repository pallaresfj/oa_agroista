<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->string('name');
            $table->string('cover')->nullable();
            $table->foreignId('school_profile_id')->constrained()->cascadeOnDelete(); // Relación
            $table->text('justification')->nullable();
            $table->text('objectives')->nullable();
            $table->text('methodology')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
