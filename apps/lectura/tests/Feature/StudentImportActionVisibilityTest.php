<?php

use App\Filament\Resources\StudentResource\Pages\ListStudents;
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

it('shows the import excel action to users that can create students', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    Livewire::actingAs($admin)
        ->test(ListStudents::class)
        ->assertSee('Importar Excel');
});
