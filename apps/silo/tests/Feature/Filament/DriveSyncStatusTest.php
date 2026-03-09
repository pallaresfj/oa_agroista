<?php

use App\Models\DriveSyncState;
use App\Models\User;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PanelAccessSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('shows the external sync progress banner in the documents list', function () {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SOPORTE);

    DriveSyncState::query()->create([
        'key' => 'documents_root',
        'root_folder_id' => 'root-folder',
        'metadata' => [
            'execution' => [
                'status' => DriveSyncState::EXECUTION_STATUS_RUNNING,
                'mode' => 'bootstrap',
                'items_processed' => 125,
                'items_total' => 1000,
                'requested_by' => 'Admin Sync',
                'requested_at' => now()->subMinute()->toIso8601String(),
                'started_at' => now()->subMinute()->toIso8601String(),
                'heartbeat_at' => now()->toIso8601String(),
                'summary' => [
                    'imported_total' => 37,
                    'imported_unclassified' => 37,
                    'skipped_existing' => 10,
                    'skipped_outside_root' => 0,
                    'errors' => 0,
                ],
            ],
        ],
    ]);

    actingAs($admin);
    $this->withoutVite();

    $response = $this->get('/admin/documents');

    $response->assertStatus(200);
    $response->assertSee('Sincronización de externos en curso.');
    $response->assertSee('Importados hasta ahora: 37.');
    $response->assertSee('Estado sync: 37 importados (125/1.000)');
});
