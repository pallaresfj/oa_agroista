<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('user_app_access');
    }

    public function down(): void
    {
        Schema::create('user_app_access', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_id')->index();
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'client_id']);
        });
    }
};
