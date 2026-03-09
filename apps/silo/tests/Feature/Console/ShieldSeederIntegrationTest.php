<?php

use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('generates shield permissions and applies canonical role matrix', function () {
    Artisan::call('shield:generate', [
        '--all' => true,
        '--panel' => 'admin',
        '--option' => 'permissions',
    ]);

    $this->seed([
        RoleSeeder::class,
        PanelAccessSeeder::class,
        RolePermissionSeeder::class,
    ]);

    $superAdmin = Role::query()->where('name', 'super_admin')->firstOrFail();
    $soporte = Role::query()->where('name', 'soporte')->firstOrFail();
    $directivo = Role::query()->where('name', 'directivo')->firstOrFail();
    $docente = Role::query()->where('name', 'docente')->firstOrFail();
    $administrativo = Role::query()->where('name', 'administrativo')->firstOrFail();
    $visitante = Role::query()->where('name', 'visitante')->firstOrFail();

    $superAdminPermissions = $superAdmin->permissions->pluck('name');
    $soportePermissions = $soporte->permissions->pluck('name');
    $directivoPermissions = $directivo->permissions->pluck('name');
    $docentePermissions = $docente->permissions->pluck('name');
    $administrativoPermissions = $administrativo->permissions->pluck('name');
    $visitantePermissions = $visitante->permissions->pluck('name');

    expect($superAdminPermissions)->toContain('view_any_role');
    expect($soportePermissions)->toContain('view_any_role');

    expect($directivoPermissions)->toContain('view_any_document');
    expect($directivoPermissions)->toContain('create_document');
    expect($directivoPermissions)->not->toContain('view_any_user');
    expect($directivoPermissions)->not->toContain('view_any_role');

    expect($docentePermissions)->toContain('view_any_document');
    expect($docentePermissions)->not->toContain('create_document');
    expect($docentePermissions)->not->toContain('view_any_role');

    expect($administrativoPermissions)->toContain('create_document');
    expect($administrativoPermissions)->toContain('update_document');
    expect($administrativoPermissions)->not->toContain('view_any_role');

    expect($visitantePermissions)->toContain('view_any_document');
    expect($visitantePermissions)->not->toContain('create_document');
});
