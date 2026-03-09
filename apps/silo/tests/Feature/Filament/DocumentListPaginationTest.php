<?php

use App\Filament\Resources\DocumentResource\Pages\ListDocuments;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Database\Seeders\PanelAccessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        PanelAccessSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('renders the document list with the expected pagination selector options', function () {
    $rector = createPaginationUserWithRole(User::ROLE_DIRECTIVO);
    actingAs($rector);

    $this->get('/admin/documents')->assertOk();

    $category = createPaginationCategory();

    foreach (range(1, 11) as $index) {
        createPaginationDocument($category, $index);
    }

    Livewire::test(ListDocuments::class)
        ->call('loadTable')
        ->assertSeeHtml('<option value="10">')
        ->assertSeeHtml('<option value="20">')
        ->assertSeeHtml('<option value="50">')
        ->assertSeeHtml('<option value="100">')
        ->assertSeeHtml('<option value="200">');
});

it('responds successfully on the documents index route', function () {
    $rector = createPaginationUserWithRole(User::ROLE_DIRECTIVO);
    actingAs($rector);

    $response = $this->get('/admin/documents');

    $response->assertOk();
});

it('defaults the document list pagination to ten records per page', function () {
    $rector = createPaginationUserWithRole(User::ROLE_DIRECTIVO);
    actingAs($rector);

    $category = createPaginationCategory();

    foreach (range(1, 11) as $index) {
        createPaginationDocument($category, $index);
    }

    Livewire::test(ListDocuments::class)
        ->call('loadTable')
        ->assertSet('tableRecordsPerPage', 10)
        ->assertSeeHtml('<option value="10">')
        ->assertSeeHtml('<option value="20">')
        ->assertSeeHtml('<option value="50">')
        ->assertSeeHtml('<option value="100">')
        ->assertSeeHtml('<option value="200">');
});

function createPaginationCategory(): DocumentCategory
{
    return DocumentCategory::create([
        'name' => 'Paginacion',
        'slug' => 'paginacion',
        'color' => '#3B82F6',
    ]);
}

function createPaginationDocument(DocumentCategory $category, int $index): Document
{
    return Document::create([
        'file_name' => "documento-{$index}.pdf",
        'title' => "Documento {$index}",
        'year' => 2026,
        'category_id' => $category->id,
        'entity_id' => null,
        'status' => 'Publicado',
        'metadata' => null,
    ]);
}

function createPaginationUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}
