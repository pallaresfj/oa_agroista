<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 32)->default('user')->after('email')->index();
            });
        }

        $superAdmins = array_values(array_filter(array_map(
            static fn (string $item): string => mb_strtolower(trim($item)),
            explode(',', (string) env('SUPERADMIN_EMAILS', '')),
        )));

        if ($superAdmins === []) {
            return;
        }

        DB::table('users')
            ->whereIn(DB::raw('LOWER(email)'), $superAdmins)
            ->update(['role' => 'superadmin']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
