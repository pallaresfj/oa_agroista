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
        Schema::create('non_working_days', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name'); // Nombre del día (ej: "Día del Trabajo", "Vacaciones de mitad de año")
            $table->enum('type', ['holiday', 'vacation', 'special'])->default('holiday');
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete(); // null = aplica a todas las sedes
            $table->boolean('is_recurring')->default(false); // Para festivos que se repiten cada año
            $table->text('description')->nullable();
            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index(['date', 'campus_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_working_days');
    }
};
