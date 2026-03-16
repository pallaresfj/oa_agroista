<?php

use App\Filament\Pages\DocenteDashboard;
use App\Models\Course;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

function makeDocenteWithCourses(array $courses): User
{
    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);
    $docente->assignedCourses()->sync(collect($courses)->pluck('id')->all());

    return $docente;
}

function createCompletedAttempt(User $docente, Student $student, ReadingPassage $passage, float $wpm, int $errors, \Carbon\CarbonInterface $finishedAt): ReadingAttempt
{
    return ReadingAttempt::query()->create([
        'student_id' => $student->id,
        'teacher_id' => $docente->id,
        'passage_id' => $passage->id,
        'status' => ReadingAttempt::STATUS_COMPLETED,
        'started_at' => $finishedAt->copy()->subMinute(),
        'finished_at' => $finishedAt,
        'duration_seconds' => 60,
        'word_count' => $passage->word_count,
        'words_per_minute' => $wpm,
        'total_errors' => $errors,
    ]);
}

it('renders teacher dashboard cards with computed pcpm', function (): void {
    $course = Course::query()->create(['name' => 'Grado 2 A']);
    $docente = makeDocenteWithCourses([$course]);

    $student = Student::query()->create([
        'name' => 'Mateo Garcia',
        'course_id' => $course->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Lectura Uno',
        'content' => 'uno dos tres cuatro cinco seis siete ocho nueve diez',
        'is_active' => true,
    ]);

    createCompletedAttempt($docente, $student, $passage, 115, 5, now()->subDay());
    createCompletedAttempt($docente, $student, $passage, 100, 10, now()->subDays(3));

    Livewire::actingAs($docente)
        ->test(DocenteDashboard::class)
        ->assertSee('Panel del Docente')
        ->assertDontSee('Panel de lectura')
        ->assertSee('PCPM')
        ->assertSee('Mateo Garcia')
        ->assertSee('110')
        ->assertSee('Nivel avanzado');
});

it('filters student cards by selected course', function (): void {
    $courseA = Course::query()->create(['name' => 'Grado 1 A']);
    $courseB = Course::query()->create(['name' => 'Grado 1 B']);
    $docente = makeDocenteWithCourses([$courseA, $courseB]);

    $studentA = Student::query()->create([
        'name' => 'Ana Curso A',
        'course_id' => $courseA->id,
    ]);

    $studentB = Student::query()->create([
        'name' => 'Bruno Curso B',
        'course_id' => $courseB->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Lectura Filtro',
        'content' => 'uno dos tres cuatro cinco seis siete ocho',
        'is_active' => true,
    ]);

    createCompletedAttempt($docente, $studentA, $passage, 102, 1, now()->subDay());
    createCompletedAttempt($docente, $studentB, $passage, 84, 2, now()->subDays(2));

    Livewire::actingAs($docente)
        ->test(DocenteDashboard::class)
        ->assertSee('Ana Curso A')
        ->assertSee('Bruno Curso B')
        ->set('selectedCourseId', $courseA->id)
        ->assertSee('Ana Curso A')
        ->assertDontSee('Bruno Curso B');
});

it('sorts cards by recency, pcpm and name', function (): void {
    $course = Course::query()->create(['name' => 'Grado 2 B']);
    $docente = makeDocenteWithCourses([$course]);

    $studentRecent = Student::query()->create(['name' => 'Ana Reciente', 'course_id' => $course->id]);
    $studentMid = Student::query()->create(['name' => 'Beto Medio', 'course_id' => $course->id]);
    $studentOld = Student::query()->create(['name' => 'Carlos Alto', 'course_id' => $course->id]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Lectura Orden',
        'content' => 'uno dos tres cuatro cinco seis siete ocho nueve',
        'is_active' => true,
    ]);

    createCompletedAttempt($docente, $studentRecent, $passage, 95, 5, now()->subHours(2)); // PCPM 90
    createCompletedAttempt($docente, $studentMid, $passage, 78, 8, now()->subDay()); // PCPM 70
    createCompletedAttempt($docente, $studentOld, $passage, 124, 4, now()->subDays(3)); // PCPM 120

    Livewire::actingAs($docente)
        ->test(DocenteDashboard::class)
        ->assertSeeInOrder(['Ana Reciente', 'Beto Medio', 'Carlos Alto'])
        ->set('sortOption', 'pcpm_high')
        ->assertSeeInOrder(['Carlos Alto', 'Ana Reciente', 'Beto Medio'])
        ->set('sortOption', 'pcpm_low')
        ->assertSeeInOrder(['Beto Medio', 'Ana Reciente', 'Carlos Alto'])
        ->set('sortOption', 'name')
        ->assertSeeInOrder(['Ana Reciente', 'Beto Medio', 'Carlos Alto']);
});

it('exports csv using current filter and includes expected data', function (): void {
    $courseA = Course::query()->create(['name' => 'Grado 3 A']);
    $courseB = Course::query()->create(['name' => 'Grado 3 B']);
    $docente = makeDocenteWithCourses([$courseA, $courseB]);

    $studentA = Student::query()->create(['name' => 'Eva Export A', 'course_id' => $courseA->id]);
    $studentB = Student::query()->create(['name' => 'Fede Export B', 'course_id' => $courseB->id]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Lectura Export',
        'content' => 'uno dos tres cuatro cinco seis siete ocho nueve diez once',
        'is_active' => true,
    ]);

    createCompletedAttempt($docente, $studentA, $passage, 108, 8, now()->subDay());
    createCompletedAttempt($docente, $studentB, $passage, 95, 5, now()->subDays(2));

    $component = Livewire::actingAs($docente)
        ->test(DocenteDashboard::class)
        ->set('selectedCourseId', $courseA->id);

    $component
        ->call('exportCsv')
        ->assertFileDownloaded('dashboard-docente.csv');

    $response = $component->instance()->exportCsv();

    expect($response)->toBeInstanceOf(StreamedResponse::class);

    ob_start();
    $response->sendContent();
    $content = (string) ob_get_clean();

    expect($content)
        ->toContain('Estudiante,Grupo,PCPM,WPM,Errores,"Fecha última evaluación",Estado,Delta')
        ->toContain('Eva Export A')
        ->toContain('Grado 3 A')
        ->not->toContain('Fede Export B');
});

it('applies threshold labels for advanced expected and reinforcement levels', function (): void {
    $course = Course::query()->create(['name' => 'Grado 4 A']);
    $docente = makeDocenteWithCourses([$course]);

    $studentAdvanced = Student::query()->create(['name' => 'Ana Avanzada', 'course_id' => $course->id]);
    $studentExpected = Student::query()->create(['name' => 'Beto Esperado', 'course_id' => $course->id]);
    $studentReinforcement = Student::query()->create(['name' => 'Cami Refuerzo', 'course_id' => $course->id]);

    $passage = ReadingPassage::query()->create([
        'title' => 'Lectura Umbrales',
        'content' => 'uno dos tres cuatro cinco seis siete ocho nueve diez once doce',
        'is_active' => true,
    ]);

    createCompletedAttempt($docente, $studentAdvanced, $passage, 112, 5, now()->subHours(3)); // 107
    createCompletedAttempt($docente, $studentExpected, $passage, 90, 5, now()->subHours(4)); // 85
    createCompletedAttempt($docente, $studentReinforcement, $passage, 70, 10, now()->subHours(5)); // 60

    Livewire::actingAs($docente)
        ->test(DocenteDashboard::class)
        ->assertSee('Nivel avanzado')
        ->assertSee('Nivel esperado')
        ->assertSee('Refuerzo requerido');
});

it('shows legacy widgets for non-docente users', function (): void {
    $directivo = User::factory()->create();
    $directivo->assignRole(User::ROLE_DIRECTIVO);

    $this->actingAs($directivo)
        ->get('/app/dashboard')
        ->assertStatus(200)
        ->assertSee('Intentos recientes');
});
