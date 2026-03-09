<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Notifications\DriveUnclassifiedDetected;
use App\Support\Drive\Contracts\DriveSyncGateway;
use App\Support\Drive\DriveUnclassifiedSyncService;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\FakeDriveSyncGateway;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PanelAccessSeeder::class,
        RolePermissionSeeder::class,
    ]);

    config()->set('filesystems.disks.google.folder', 'root-folder');
});

it('supports legacy notify_roles values by resolving canonical role recipients', function () {
    config()->set('drive_sync.notify', true);
    config()->set('drive_sync.notify_roles', ['administrador', 'rector']);

    $administrativo = User::factory()->create([
        'email' => 'administrativo@example.com',
    ]);
    $administrativo->assignRole(User::ROLE_ADMINISTRATIVO);

    $directivo = User::factory()->create([
        'email' => 'rector@example.com',
    ]);
    $directivo->assignRole(User::ROLE_DIRECTIVO);

    $docente = User::factory()->create([
        'email' => 'editor@example.com',
    ]);
    $docente->assignRole(User::ROLE_DOCENTE);

    Notification::fake();

    app()->instance(DriveSyncGateway::class, new FakeDriveSyncGateway(
        rootMetadata: ['id' => 'root-folder', 'name' => 'SGI_SILO_DOC', 'driveId' => 'drive-1'],
        startPageToken: 'token-bootstrap',
        recursiveFiles: [
            [
                'id' => 'unclassified-1',
                'name' => 'externo.pdf',
                'mimeType' => 'application/pdf',
                'parents' => ['random-folder'],
                'trashed' => false,
                'webViewLink' => 'https://drive.google.com/file/d/unclassified-1/view',
                'path' => '/foo/bar/externo.pdf',
            ],
        ],
    ));

    app(DriveUnclassifiedSyncService::class)->sync(true);

    Notification::assertSentTo($administrativo, DriveUnclassifiedDetected::class);
    Notification::assertSentTo($directivo, DriveUnclassifiedDetected::class);
    Notification::assertNotSentTo($docente, DriveUnclassifiedDetected::class);
});

it('does not send notifications when there are no unclassified imports', function () {
    config()->set('drive_sync.notify', true);
    config()->set('drive_sync.notify_roles', ['administrador', 'rector']);

    $rector = User::factory()->create([
        'email' => 'rector@example.com',
    ]);
    $rector->assignRole(User::ROLE_DIRECTIVO);

    Notification::fake();

    app()->instance(DriveSyncGateway::class, new FakeDriveSyncGateway(
        rootMetadata: ['id' => 'root-folder', 'name' => 'SGI_SILO_DOC', 'driveId' => 'drive-1'],
        startPageToken: 'token-bootstrap',
        recursiveFiles: [],
    ));

    app(DriveUnclassifiedSyncService::class)->sync(true);

    Notification::assertNotSentTo($rector, DriveUnclassifiedDetected::class);
});

it('shows the unclassified alert banner in dashboard', function () {
    $rector = User::factory()->create();
    $rector->assignRole(User::ROLE_DIRECTIVO);
    actingAs($rector);
    $this->withoutVite();

    $category = DocumentCategory::query()->create([
        'name' => 'Sin clasificar',
        'slug' => 'sin-clasificar',
        'color' => '#3B82F6',
    ]);

    Document::query()->create([
        'gdrive_id' => 'ext-1',
        'gdrive_url' => 'https://drive.google.com/file/d/ext-1/view',
        'file_name' => 'externo.pdf',
        'title' => 'Archivo externo',
        'year' => 2026,
        'category_id' => $category->id,
        'entity_id' => null,
        'status' => 'Importado_Sin_Clasificar',
        'metadata' => [
            'import_source' => 'drive_changes_api',
            'import_path' => '/2026/sin-clasificar/externo.pdf',
        ],
    ]);

    $response = $this->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Archivos Sin Clasificar');
    $response->assertSee('Revisar ahora');
    $response->assertSee('Ruta detectada:', false);
    $response->assertSee('filters%5Bstatus%5D%5Bvalue%5D=Importado_Sin_Clasificar', false);
});
