<?php

use App\Models\User;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(PanelAccessSeeder::class);
    Artisan::call('shield:generate', [
        '--all' => true,
        '--panel' => 'app',
        '--option' => 'permissions',
    ]);
    $this->seed(RolePermissionSeeder::class);
});

it('allows soporte to access shield role resource', function () {
    $user = User::factory()->create([
        'name' => 'Soporte',
        'email' => 'soporte.test@iedagropivijay.edu.co',
        'is_active' => true,
    ]);

    $user->assignRole('soporte');

    $response = $this
        ->actingAs($user, 'web')
        ->get('/app/shield/roles');

    $response->assertOk();
});

it('denies directivo access to shield role resource', function () {
    $user = User::factory()->create([
        'name' => 'Directivo',
        'email' => 'directivo.test@iedagropivijay.edu.co',
        'is_active' => true,
    ]);

    $user->assignRole('directivo');

    $response = $this
        ->actingAs($user, 'web')
        ->get('/app/shield/roles');

    $response->assertForbidden();
});
