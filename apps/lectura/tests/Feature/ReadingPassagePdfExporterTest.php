<?php

use App\Models\ReadingPassage;
use App\Services\ReadingPassagePdfExporter;

it('generates valid pdf bytes for a reading passage', function (): void {
    $passage = new ReadingPassage([
        'title' => 'Lectura de prueba',
        'content' => 'uno dos tres cuatro cinco seis',
    ]);

    $pdf = app(ReadingPassagePdfExporter::class)->makePdf($passage);

    $today = now()->format('d/m/Y');

    expect(str_starts_with($pdf, '%PDF-1.4'))->toBeTrue()
        ->and(str_contains($pdf, '%%EOF'))->toBeTrue()
        ->and(str_contains($pdf, 'Fluidez Lectora'))->toBeTrue()
        ->and(str_contains($pdf, "Fecha: {$today}"))->toBeTrue()
        ->and(str_contains($pdf, 'Lectura de prueba'))->toBeTrue();
});

it('paginates long passages across multiple pages', function (): void {
    $passage = new ReadingPassage([
        'title' => 'Lectura extensa',
        'content' => implode(' ', array_fill(0, 5000, 'palabra')),
    ]);

    $pdf = app(ReadingPassagePdfExporter::class)->makePdf($passage);

    expect(substr_count($pdf, '/Type /Page /Parent'))->toBeGreaterThan(1);
});

it('returns a download response with pdf headers', function (): void {
    $passage = new ReadingPassage([
        'title' => 'Texto imprimible',
        'content' => 'contenido listo para impresión',
    ]);

    $response = app(ReadingPassagePdfExporter::class)->download($passage);

    expect((string) $response->headers->get('content-type'))->toContain('application/pdf')
        ->and((string) $response->headers->get('content-disposition'))->toContain('attachment;')
        ->and((string) $response->headers->get('content-disposition'))->toContain('.pdf');
});

it('preserves accented vowels and eñe in generated text', function (): void {
    $passage = new ReadingPassage([
        'title' => 'Canción del niño',
        'content' => 'María soñó con piñatas y acción en otoño.',
    ]);

    $pdf = app(ReadingPassagePdfExporter::class)->makePdf($passage);

    $needleTitle = iconv('UTF-8', 'windows-1252//IGNORE', 'Canción del niño');
    $needleContent = iconv('UTF-8', 'windows-1252//IGNORE', 'María soñó con piñatas y acción en otoño.');

    expect($needleTitle)->not->toBeFalse()
        ->and($needleContent)->not->toBeFalse()
        ->and(str_contains($pdf, (string) $needleTitle))->toBeTrue()
        ->and(str_contains($pdf, (string) $needleContent))->toBeTrue();
});
