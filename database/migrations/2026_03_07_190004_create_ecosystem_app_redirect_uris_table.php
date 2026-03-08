<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecosystem_app_redirect_uris', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ecosystem_app_id')->constrained()->cascadeOnDelete();
            $table->string('redirect_uri');
            $table->boolean('is_frontchannel_logout')->default(false);
            $table->timestamps();

            $table->unique(['ecosystem_app_id', 'redirect_uri'], 'app_redirect_uri_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecosystem_app_redirect_uris');
    }
};
