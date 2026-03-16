<?php

use App\Filament\Pages\ReadingSession;
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

it('runs reading session flow start stop and save with typed errors', function (): void {
    $course = Course::query()->create(['name' => '6A']);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);
    $docente->assignedCourses()->sync([$course->id]);

    $student = Student::query()->create([
        'name' => 'Estudiante Uno',
        'course_id' => $course->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Texto',
        'content' => implode(' ', array_fill(0, 100, 'palabra')),
        'is_active' => true,
    ]);

    Livewire::actingAs($docente)
        ->test(ReadingSession::class)
        ->set('studentId', $student->id)
        ->set('passageId', $passage->id)
        ->call('startAttempt')
        ->call('adjustErrorCount', 'omision', 2)
        ->call('adjustErrorCount', 'vacilacion', 1)
        ->call('stopAttempt')
        ->assertSet('showFinalizeModal', true)
        ->call('saveEvaluation')
        ->assertSet('showFinalizeModal', false)
        ->assertSet('activeAttemptId', null);

    $attempt = ReadingAttempt::query()
        ->where('teacher_id', $docente->id)
        ->latest('id')
        ->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt?->status)->toBe(ReadingAttempt::STATUS_COMPLETED)
        ->and($attempt?->total_errors)->toBe(3)
        ->and($attempt?->errors()->where('error_type', 'omision')->count())->toBe(2)
        ->and($attempt?->errors()->where('error_type', 'vacilacion')->count())->toBe(1);
});

it('cancels attempt when discarding reading session', function (): void {
    $course = Course::query()->create(['name' => '6B']);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);
    $docente->assignedCourses()->sync([$course->id]);

    $student = Student::query()->create([
        'name' => 'Estudiante Dos',
        'course_id' => $course->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Texto Dos',
        'content' => 'uno dos tres cuatro cinco seis siete ocho',
        'is_active' => true,
    ]);

    Livewire::actingAs($docente)
        ->test(ReadingSession::class)
        ->set('studentId', $student->id)
        ->set('passageId', $passage->id)
        ->call('startAttempt')
        ->call('discardAndReset')
        ->assertSet('showFinalizeModal', false)
        ->assertSet('activeAttemptId', null);

    $attempt = ReadingAttempt::query()
        ->where('teacher_id', $docente->id)
        ->latest('id')
        ->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt?->status)->toBe(ReadingAttempt::STATUS_CANCELLED)
        ->and($attempt?->errors()->count())->toBe(0);
});
