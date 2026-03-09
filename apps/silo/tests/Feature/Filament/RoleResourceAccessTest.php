<?php

use App\Models\User;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PanelAccessSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('allows soporte to access shield role resource', function () {
    $user = User::factory()->create([
        'email' => 'soporte.test@iedagropivijay.edu.co',
    ]);
    $user->assignRole(User::ROLE_SOPORTE);

    $this->actingAs($user, 'web')
        ->get('/admin/shield/roles')
        ->assertOk();
});

it('denies directivo access to shield role resource', function () {
    $user = User::factory()->create([
        'email' => 'directivo.test@iedagropivijay.edu.co',
    ]);
    $user->assignRole(User::ROLE_DIRECTIVO);

    $this->actingAs($user, 'web')
        ->get('/admin/shield/roles')
        ->assertForbidden();
});

it('redirects legacy /admin/roles to shield role resource', function () {
    $user = User::factory()->create([
        'email' => 'soporte.redirect@iedagropivijay.edu.co',
    ]);
    $user->assignRole(User::ROLE_SOPORTE);

    $this->actingAs($user, 'web')
        ->get('/admin/roles')
        ->assertRedirect('/admin/shield/roles');
});
