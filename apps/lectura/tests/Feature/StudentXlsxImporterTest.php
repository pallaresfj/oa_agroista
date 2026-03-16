<?php

use App\Models\Course;
use App\Models\Student;
use App\Services\StudentXlsxImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

uses(RefreshDatabase::class);

/**
 * @param  array<int, array<int, string>>  $rows
 */
function buildStudentsXlsx(array $rows): string
{
    $directory = storage_path('framework/testing');

    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $path = $directory.'/students-import-'.uniqid('', true).'.xlsx';

    $writer = new Writer();
    $writer->openToFile($path);

    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }

    $writer->close();

    return $path;
}

it('imports students from xlsx with curso and nombre columns', function (): void {
    Course::query()->create(['name' => 'CA0602', 'is_active' => true]);
    Course::query()->create(['name' => 'CA0603', 'is_active' => true]);

    $path = buildStudentsXlsx([
        ['curso', 'nombre'],
        ['CA0602', 'Ana Pérez'],
        ['CA0602', 'Ana Pérez'],
        ['CA0603', 'Luis Gómez'],
    ]);

    try {
        $result = app(StudentXlsxImporter::class)->import($path);
    } finally {
        @unlink($path);
    }

    expect($result['total_data_rows'])->toBe(3)
        ->and($result['created'])->toBe(2)
        ->and($result['skipped'])->toBe(1)
        ->and($result['failed'])->toBe(0)
        ->and(Student::query()->count())->toBe(2)
        ->and(Student::query()->where('name', 'Ana Pérez')->exists())->toBeTrue()
        ->and(Student::query()->where('name', 'Luis Gómez')->exists())->toBeTrue();
});

it('reports failed rows when course does not exist', function (): void {
    Course::query()->create(['name' => 'CA0602', 'is_active' => true]);

    $path = buildStudentsXlsx([
        ['curso', 'nombre'],
        ['CA0602', 'Ana Pérez'],
        ['NO_EXISTE', 'Pedro Niño'],
    ]);

    try {
        $result = app(StudentXlsxImporter::class)->import($path);
    } finally {
        @unlink($path);
    }

    expect($result['total_data_rows'])->toBe(2)
        ->and($result['created'])->toBe(1)
        ->and($result['failed'])->toBe(1)
        ->and($result['errors'])->not->toBe([])
        ->and($result['errors'][0])->toContain("el curso 'NO_EXISTE' no existe")
        ->and(Student::query()->count())->toBe(1);
});
