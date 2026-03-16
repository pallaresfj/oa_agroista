<?php

use App\Filament\Resources\ReadingAttemptResource;
use App\Filament\Resources\ReadingAttemptResource\Pages\EditReadingAttempt;
use App\Models\ReadingError;
use App\Filament\Resources\StudentResource;
use App\Models\Course;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('applies role capabilities for soporte, directivo and docente', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

    $soporte = User::factory()->create();
    $soporte->assignRole(User::ROLE_SOPORTE);

    $directivo = User::factory()->create();
    $directivo->assignRole(User::ROLE_DIRECTIVO);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);

    expect($superAdmin->isAdminEquivalent())->toBeTrue()
        ->and($soporte->isAdminEquivalent())->toBeTrue()
        ->and($docente->canManageReadingOperations())->toBeTrue()
        ->and($directivo->canManageReadingOperations())->toBeFalse()
        ->and($directivo->isDirectivo())->toBeTrue();
});

it('filters students for docente by assigned courses in student resource query', function (): void {
    $courseA = Course::query()->create(['name' => '4A']);
    $courseB = Course::query()->create(['name' => '4B']);

    $studentA = Student::query()->create(['name' => 'Ana', 'course_id' => $courseA->id]);
    $studentB = Student::query()->create(['name' => 'Beto', 'course_id' => $courseB->id]);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);
    $docente->assignedCourses()->sync([$courseA->id]);

    $this->actingAs($docente);

    $visibleStudentIds = StudentResource::getEloquentQuery()
        ->pluck('students.id')
        ->all();

    expect($visibleStudentIds)->toBe([$studentA->id])
        ->and($visibleStudentIds)->not->toContain($studentB->id);
});

it('allows edit and delete attempts only for admin equivalent or owning docente', function (): void {
    $course = Course::query()->create(['name' => '5A']);

    $ownerDocente = User::factory()->create();
    $ownerDocente->assignRole(User::ROLE_DOCENTE);

    $otherDocente = User::factory()->create();
    $otherDocente->assignRole(User::ROLE_DOCENTE);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

    $soporte = User::factory()->create();
    $soporte->assignRole(User::ROLE_SOPORTE);

    $directivo = User::factory()->create();
    $directivo->assignRole(User::ROLE_DIRECTIVO);

    $student = Student::query()->create([
        'name' => 'Estudiante',
        'course_id' => $course->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Lectura',
        'content' => 'uno dos tres cuatro cinco',
        'is_active' => true,
    ]);

    $attempt = ReadingAttempt::query()->create([
        'student_id' => $student->id,
        'teacher_id' => $ownerDocente->id,
        'passage_id' => $passage->id,
        'status' => ReadingAttempt::STATUS_COMPLETED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'duration_seconds' => 60,
        'word_count' => $passage->word_count,
        'words_per_minute' => 5,
        'total_errors' => 0,
    ]);

    $this->actingAs($ownerDocente);
    expect(ReadingAttemptResource::canEdit($attempt))->toBeTrue()
        ->and(ReadingAttemptResource::canDelete($attempt))->toBeTrue();

    $this->actingAs($otherDocente);
    expect(ReadingAttemptResource::canEdit($attempt))->toBeFalse()
        ->and(ReadingAttemptResource::canDelete($attempt))->toBeFalse();

    $this->actingAs($superAdmin);
    expect(ReadingAttemptResource::canEdit($attempt))->toBeTrue();

    $this->actingAs($soporte);
    expect(ReadingAttemptResource::canEdit($attempt))->toBeTrue();

    $this->actingAs($directivo);
    expect(ReadingAttemptResource::canEdit($attempt))->toBeFalse()
        ->and(ReadingAttemptResource::canDelete($attempt))->toBeFalse();
});

it('recalculates total errors when editing an attempt', function (): void {
    $course = Course::query()->create(['name' => '7A']);

    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);

    $student = Student::query()->create([
        'name' => 'Estudiante',
        'course_id' => $course->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Texto',
        'content' => 'uno dos tres cuatro',
        'is_active' => true,
    ]);

    $attempt = ReadingAttempt::query()->create([
        'student_id' => $student->id,
        'teacher_id' => $docente->id,
        'passage_id' => $passage->id,
        'status' => ReadingAttempt::STATUS_COMPLETED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'duration_seconds' => 60,
        'word_count' => $passage->word_count,
        'words_per_minute' => 4,
        'total_errors' => 0,
    ]);

    $attempt->errors()->create([
        'error_type' => 'omision',
        'occurred_at_seconds' => 5,
    ]);

    Livewire::actingAs($admin)
        ->test(EditReadingAttempt::class, ['record' => $attempt->getKey()])
        ->fillForm([
            'notes' => 'Ajustado por revisión',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $attempt->refresh();

    expect($attempt->total_errors)->toBe(1)
        ->and($attempt->errors()->count())->toBe(1);
});

it('deletes attempt errors when an attempt is removed', function (): void {
    $course = Course::query()->create(['name' => '8A']);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);

    $student = Student::query()->create([
        'name' => 'Estudiante',
        'course_id' => $course->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Texto',
        'content' => 'uno dos tres cuatro cinco',
        'is_active' => true,
    ]);

    $attempt = ReadingAttempt::query()->create([
        'student_id' => $student->id,
        'teacher_id' => $docente->id,
        'passage_id' => $passage->id,
        'status' => ReadingAttempt::STATUS_COMPLETED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'duration_seconds' => 60,
        'word_count' => $passage->word_count,
        'words_per_minute' => 5,
    ]);

    $error = ReadingError::query()->create([
        'attempt_id' => $attempt->id,
        'error_type' => 'omision',
        'occurred_at_seconds' => 10,
    ]);

    $attempt->delete();

    $this->assertDatabaseMissing('reading_errors', ['id' => $error->id]);
});
