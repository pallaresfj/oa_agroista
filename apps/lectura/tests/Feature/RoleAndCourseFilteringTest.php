<?php

use App\Filament\Resources\CourseResource;
use App\Filament\Resources\ReadingAttemptResource;
use App\Filament\Resources\ReadingAttemptResource\Pages\EditReadingAttempt;
use App\Filament\Resources\ReadingPassageResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\UserResource;
use App\Models\Course;
use App\Models\ReadingAttempt;
use App\Models\ReadingError;
use App\Models\ReadingPassage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Artisan::call('shield:generate', [
        '--all' => true,
        '--panel' => 'app',
        '--option' => 'permissions',
    ]);
    $this->seed(PanelAccessSeeder::class);
    $this->seed(RolePermissionSeeder::class);
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

it('enforces the role matrix for panel resources', function (): void {
    $course = Course::query()->create(['name' => '6A']);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

    $soporte = User::factory()->create();
    $soporte->assignRole(User::ROLE_SOPORTE);

    $directivo = User::factory()->create();
    $directivo->assignRole(User::ROLE_DIRECTIVO);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);
    $docente->assignedCourses()->sync([$course->id]);

    $student = Student::query()->create([
        'name' => 'Estudiante Matriz',
        'course_id' => $course->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Lectura Matriz',
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
        'total_errors' => 0,
    ]);

    $this->actingAs($superAdmin);
    expect(RoleResource::canAccess())->toBeTrue()
        ->and(UserResource::canAccess())->toBeTrue()
        ->and(CourseResource::canAccess())->toBeTrue()
        ->and(StudentResource::canCreate())->toBeTrue()
        ->and(ReadingPassageResource::canCreate())->toBeTrue()
        ->and(ReadingAttemptResource::canEdit($attempt))->toBeTrue();

    $this->actingAs($soporte);
    expect(RoleResource::canAccess())->toBeTrue()
        ->and(UserResource::canAccess())->toBeTrue()
        ->and(CourseResource::canAccess())->toBeTrue()
        ->and(StudentResource::canCreate())->toBeTrue()
        ->and(ReadingPassageResource::canCreate())->toBeTrue()
        ->and(ReadingAttemptResource::canEdit($attempt))->toBeTrue();

    $this->actingAs($docente);
    expect(RoleResource::canAccess())->toBeFalse()
        ->and(UserResource::canAccess())->toBeFalse()
        ->and(CourseResource::canAccess())->toBeFalse()
        ->and(StudentResource::canAccess())->toBeTrue()
        ->and(StudentResource::canCreate())->toBeFalse()
        ->and(StudentResource::canEdit($student))->toBeFalse()
        ->and(StudentResource::canDelete($student))->toBeFalse()
        ->and(ReadingPassageResource::canAccess())->toBeTrue()
        ->and(ReadingPassageResource::canCreate())->toBeFalse()
        ->and(ReadingAttemptResource::canViewAny())->toBeTrue()
        ->and(ReadingAttemptResource::canEdit($attempt))->toBeTrue();

    $this->actingAs($directivo);
    expect(RoleResource::canAccess())->toBeFalse()
        ->and(UserResource::canAccess())->toBeFalse()
        ->and(CourseResource::canAccess())->toBeFalse()
        ->and(StudentResource::canAccess())->toBeTrue()
        ->and(StudentResource::canCreate())->toBeFalse()
        ->and(StudentResource::canEdit($student))->toBeFalse()
        ->and(StudentResource::canDelete($student))->toBeFalse()
        ->and(ReadingPassageResource::canAccess())->toBeTrue()
        ->and(ReadingPassageResource::canCreate())->toBeFalse()
        ->and(ReadingPassageResource::canEdit($passage))->toBeFalse()
        ->and(ReadingAttemptResource::canViewAny())->toBeTrue()
        ->and(ReadingAttemptResource::canView($attempt))->toBeTrue()
        ->and(ReadingAttemptResource::canEdit($attempt))->toBeFalse()
        ->and(ReadingAttemptResource::canDelete($attempt))->toBeFalse();
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
