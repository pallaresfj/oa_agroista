<?php

use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates reading metrics when an attempt is completed', function (): void {
    $teacher = User::factory()->create();
    $student = Student::query()->create([
        'name' => 'Estudiante Uno',
        'student_code' => 'STU-001',
        'is_active' => true,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Texto de prueba',
        'content' => implode(' ', array_fill(0, 120, 'palabra')),
        'is_active' => true,
    ]);

    $startedAt = now()->startOfDay()->addHours(8);
    $finishedAt = (clone $startedAt)->addSeconds(90);

    $attempt = ReadingAttempt::query()->create([
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'passage_id' => $passage->id,
        'status' => ReadingAttempt::STATUS_IN_PROGRESS,
        'started_at' => $startedAt,
        'word_count' => $passage->word_count,
    ]);

    $attempt->registerError(\App\Enums\ReadingErrorType::OMISION, 8);
    $attempt->registerError(\App\Enums\ReadingErrorType::VACILACION, 31);
    $attempt->complete($finishedAt);
    $attempt->refresh();

    expect($attempt->status)->toBe(ReadingAttempt::STATUS_COMPLETED)
        ->and($attempt->duration_seconds)->toBe(90)
        ->and((float) $attempt->words_per_minute)->toBe(80.0)
        ->and($attempt->total_errors)->toBe(2);
});

it('recomputes the stored word count when a passage changes', function (): void {
    $passage = ReadingPassage::query()->create([
        'title' => 'Conteo',
        'content' => 'uno dos tres',
        'is_active' => true,
    ]);

    expect($passage->word_count)->toBe(3);

    $passage->update([
        'content' => 'uno dos tres cuatro cinco',
    ]);

    expect($passage->fresh()->word_count)->toBe(5);
});
