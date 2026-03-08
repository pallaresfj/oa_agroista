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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('item');
            $table->integer('quantity')->default();
            $table->decimal('unit_value', 10, 2)->default(0);
            $table->decimal('total_value', 12, 2)->storedAs('quantity * unit_value');
            $table->text('observations')->nullable();
            $table->unsignedBigInteger('center_id');
            $table->timestamps();
            $table->foreign('center_id')->references('id')->on('centers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
