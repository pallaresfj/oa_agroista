<?php

use App\Filament\Pages\ReadingSession;
use App\Filament\Resources\ReadingPassageResource;
use App\Filament\Resources\ReadingPassageResource\Pages\CreateReadingPassage;
use App\Filament\Resources\ReadingPassageResource\Pages\EditReadingPassage;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\CreateStudent;
use App\Filament\Resources\StudentResource\Pages\EditStudent;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Course;
use App\Models\ReadingPassage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

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

function makeAdminWithRolePermissions(): User
{
    $superAdminRole = Role::query()->firstOrCreate([
        'name' => User::ROLE_SUPER_ADMIN,
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->assignRole($superAdminRole);

    return $user;
}

it('renders reading session with base blocks on initial load', function (): void {
    $course = Course::query()->create(['name' => 'Inicial']);

    $docente = User::factory()->create();
    $docente->assignRole(User::ROLE_DOCENTE);
    $docente->assignedCourses()->sync([$course->id]);

    Student::query()->create([
        'name' => 'Estudiante Inicial',
        'course_id' => $course->id,
    ]);

    ReadingPassage::query()->create([
        'title' => 'Lectura Inicial',
        'content' => 'uno dos tres cuatro cinco',
        'is_active' => true,
    ]);

    Livewire::actingAs($docente)
        ->test(ReadingSession::class)
        ->assertSee('Estudiante')
        ->assertSee('Lectura')
        ->assertSee('Iniciar')
        ->assertSee('Texto de lectura');
});

it('redirects to student index after create and edit', function (): void {
    $admin = makeAdminWithRolePermissions();
    $course = Course::query()->create(['name' => '4A']);

    Livewire::actingAs($admin)
        ->test(CreateStudent::class)
        ->fillForm([
            'name' => 'Estudiante Redirect',
            'course_id' => $course->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(StudentResource::getUrl('index'));

    $student = Student::query()->firstOrFail();

    Livewire::actingAs($admin)
        ->test(EditStudent::class, ['record' => $student->getKey()])
        ->fillForm([
            'name' => 'Estudiante Editado',
            'course_id' => $course->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(StudentResource::getUrl('index'));
});

it('redirects to reading passage index after create and edit', function (): void {
    $admin = makeAdminWithRolePermissions();

    Livewire::actingAs($admin)
        ->test(CreateReadingPassage::class)
        ->fillForm([
            'title' => 'Lectura Redirect',
            'difficulty_level' => 'Básico',
            'is_active' => true,
            'content' => 'uno dos tres cuatro cinco seis',
            'notes' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(ReadingPassageResource::getUrl('index'));

    $passage = ReadingPassage::query()->firstOrFail();

    Livewire::actingAs($admin)
        ->test(EditReadingPassage::class, ['record' => $passage->getKey()])
        ->fillForm([
            'title' => 'Lectura Redirect Editada',
            'difficulty_level' => 'Intermedio',
            'is_active' => true,
            'content' => 'siete ocho nueve diez',
            'notes' => 'Actualizada',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(ReadingPassageResource::getUrl('index'));
});

it('redirects to role index after create and edit', function (): void {
    $admin = makeAdminWithRolePermissions();

    Livewire::actingAs($admin)
        ->test(CreateRole::class)
        ->fillForm([
            'name' => 'coordinador_prueba',
            'guard_name' => 'web',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(RoleResource::getUrl('index'));

    $role = Role::query()
        ->where('guard_name', 'web')
        ->where('name', 'coordinador_prueba')
        ->firstOrFail();

    Livewire::actingAs($admin)
        ->test(EditRole::class, ['record' => $role->getKey()])
        ->fillForm([
            'name' => 'coordinador_prueba_editado',
            'guard_name' => 'web',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(RoleResource::getUrl('index'));
});

it('redirects to user index after create and edit', function (): void {
    $admin = makeAdminWithRolePermissions();

    $docenteRole = Role::query()->firstOrCreate([
        'name' => User::ROLE_DOCENTE,
        'guard_name' => 'web',
    ]);

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'Usuario Redirect',
            'email' => 'usuario.redirect@example.test',
            'identification_number' => '1001',
            'phone' => '3000000000',
            'roles' => $docenteRole->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(UserResource::getUrl('index'));

    $user = User::query()->where('email', 'usuario.redirect@example.test')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'name' => 'Usuario Redirect Editado',
            'email' => 'usuario.redirect@example.test',
            'identification_number' => '1001',
            'phone' => '3000000000',
            'roles' => $docenteRole->id,
            'is_active' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(UserResource::getUrl('index'));
});
