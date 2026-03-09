<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('institution_settings')) {
            return;
        }

        $duplicates = DB::table('institution_settings')
            ->select('key', DB::raw('COUNT(*) as total'))
            ->groupBy('key')
            ->having('total', '>', 1)
            ->pluck('key')
            ->all();

        foreach ($duplicates as $key) {
            $idsToKeep = DB::table('institution_settings')
                ->where('key', $key)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->limit(1)
                ->pluck('id')
                ->all();

            DB::table('institution_settings')
                ->where('key', $key)
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }

        if (Schema::hasColumn('institution_settings', 'institution_id')) {
            Schema::table('institution_settings', function (Blueprint $table): void {
                try {
                    $table->dropForeign(['institution_id']);
                } catch (Throwable) {
                    // Legacy schema may already differ.
                }
            });

            Schema::table('institution_settings', function (Blueprint $table): void {
                try {
                    $table->dropUnique(['institution_id', 'key']);
                } catch (Throwable) {
                    // Legacy schema may already differ.
                }
            });

            Schema::table('institution_settings', function (Blueprint $table): void {
                $table->dropColumn('institution_id');
            });
        }

        Schema::table('institution_settings', function (Blueprint $table): void {
            try {
                $table->unique('key');
            } catch (Throwable) {
                // Ignore when unique index already exists.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('institution_settings')) {
            return;
        }

        if (! Schema::hasColumn('institution_settings', 'institution_id')) {
            Schema::table('institution_settings', function (Blueprint $table): void {
                $table->unsignedBigInteger('institution_id')->nullable()->first();
            });
        }

        Schema::table('institution_settings', function (Blueprint $table): void {
            try {
                $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
            } catch (Throwable) {
                // Ignore when institutions table is unavailable.
            }

            try {
                $table->unique(['institution_id', 'key']);
            } catch (Throwable) {
                // Ignore duplicate/legacy index errors.
            }
        });
    }
};
