<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'course_id')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->foreignId('course_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('courses')
                    ->nullOnDelete();
            });
        }

        $now = Carbon::now();

        $fallbackCourseId = DB::table('courses')->where('name', 'Sin curso')->value('id');

        if (! $fallbackCourseId) {
            $fallbackCourseId = DB::table('courses')->insertGetId([
                'name' => 'Sin curso',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $courseByName = [];

        DB::table('students')
            ->select('id', 'grade', 'section')
            ->orderBy('id')
            ->get()
            ->each(function (object $student) use (&$courseByName, $fallbackCourseId, $now): void {
                $courseName = collect([
                    trim((string) ($student->grade ?? '')),
                    trim((string) ($student->section ?? '')),
                ])->filter()->implode(' ');

                if ($courseName === '') {
                    $courseId = $fallbackCourseId;
                } else {
                    $courseId = $courseByName[$courseName] ?? null;

                    if (! $courseId) {
                        $courseId = DB::table('courses')->where('name', $courseName)->value('id');
                    }

                    if (! $courseId) {
                        $courseId = DB::table('courses')->insertGetId([
                            'name' => $courseName,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    $courseByName[$courseName] = $courseId;
                }

                DB::table('students')
                    ->where('id', $student->id)
                    ->update([
                        'course_id' => $courseId,
                        'updated_at' => $now,
                    ]);
            });

        DB::table('students')
            ->whereNull('course_id')
            ->update([
                'course_id' => $fallbackCourseId,
                'updated_at' => $now,
            ]);

        if (Schema::hasColumn('students', 'is_active') && Schema::hasColumn('students', 'name')) {
            try {
                Schema::table('students', function (Blueprint $table): void {
                    $table->dropIndex(['is_active', 'name']);
                });
            } catch (\Throwable) {
                try {
                    Schema::table('students', function (Blueprint $table): void {
                        $table->dropIndex('students_is_active_name_index');
                    });
                } catch (\Throwable) {
                    // El índice puede no existir en algunas bases legacy.
                }
            }
        }

        if (Schema::hasColumn('students', 'student_code')) {
            try {
                Schema::table('students', function (Blueprint $table): void {
                    $table->dropUnique(['student_code']);
                });
            } catch (\Throwable) {
                try {
                    Schema::table('students', function (Blueprint $table): void {
                        $table->dropUnique('students_student_code_unique');
                    });
                } catch (\Throwable) {
                    // El índice único puede no existir según el estado de origen.
                }
            }
        }

        $columnsToDrop = collect(['student_code', 'grade', 'section', 'notes', 'is_active'])
            ->filter(fn (string $column): bool => Schema::hasColumn('students', $column))
            ->values()
            ->all();

        if ($columnsToDrop !== []) {
            Schema::table('students', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        $hasCourseId = Schema::hasColumn('students', 'course_id');

        if (! Schema::hasColumn('students', 'student_code')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('student_code', 50)->nullable()->unique()->after('name');
                $table->string('grade', 50)->nullable()->after('student_code');
                $table->string('section', 50)->nullable()->after('grade');
                $table->text('notes')->nullable()->after('section');
                $table->boolean('is_active')->default(true)->after('notes');
                $table->index(['is_active', 'name']);
            });
        }

        if ($hasCourseId) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('course_id');
            });
        }
    }
};
