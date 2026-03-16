<?php

namespace App\Services;

use App\Models\ReadingPassage;
use App\Support\Institution\InstitutionTheme;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingPassagePdfExporter
{
    public function download(ReadingPassage $passage): StreamedResponse
    {
        $filename = $this->buildFilename($passage);
        $pdf = $this->makePdf($passage);

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf;
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    public function makePdf(ReadingPassage $passage): string
    {
        $title = trim((string) $passage->title);
        $content = trim((string) $passage->content);

        $branding = InstitutionTheme::branding();
        $institutionName = trim((string) ($branding['name'] ?? 'Institucion'));
        $institutionLocation = trim((string) ($branding['location'] ?? ''));
        $institutionLine = $institutionLocation !== '' ? "{$institutionName} - {$institutionLocation}" : $institutionName;
        $today = now()->format('d/m/Y');

        $headerRows = [];
        $headerRows[] = ['font' => 'F2', 'size' => 15, 'text' => 'Fluidez Lectora'];
        $headerRows[] = ['font' => 'F1', 'size' => 11, 'text' => $institutionLine];
        $headerRows[] = ['font' => 'F1', 'size' => 11, 'text' => "Fecha: {$today}"];
        $headerRows[] = ['font' => 'F1', 'size' => 11, 'text' => ''];

        $bodyRows = [];
        $bodyRows[] = ['font' => 'F2', 'size' => 18, 'text' => $title !== '' ? $title : 'Lectura'];
        $bodyRows[] = ['font' => 'F1', 'size' => 14, 'text' => ''];

        foreach ($this->wrapText($content) as $line) {
            $bodyRows[] = ['font' => 'F1', 'size' => 14, 'text' => $line];
        }

        $pages = $this->buildPages($headerRows, $bodyRows);

        $objects = [
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            3 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>",
            4 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>",
        ];
        $kids = [];

        foreach ($pages as $index => $pageRows) {
            $pageObjectId = 5 + ($index * 2);
            $contentObjectId = $pageObjectId + 1;
            $contentStream = $this->buildContentStream($pageRows);

            $kids[] = "{$pageObjectId} 0 R";
            $objects[$pageObjectId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentObjectId} 0 R >>";
            $objects[$contentObjectId] = "<< /Length ".strlen($contentStream)." >>\nstream\n{$contentStream}\nendstream";
        }

        $objects[2] = "<< /Type /Pages /Count ".count($kids)." /Kids [".implode(' ', $kids)."] >>";

        return $this->buildPdf($objects);
    }

    /**
     * @param  array<int, array{font:string, size:int, text:string}>  $rows
     */
    private function buildContentStream(array $rows): string
    {
        $stream = "BT\n";
        $stream .= "/F1 12 Tf\n";
        $stream .= "50 760 Td\n";

        $first = true;

        foreach ($rows as $row) {
            $font = $row['font'];
            $fontSize = $row['size'];
            $line = $this->toPdfString($row['text']);
            $lineGap = $this->lineGapForSize($fontSize);

            if ($first) {
                $stream .= "/{$font} {$fontSize} Tf\n";
                $stream .= "({$line}) Tj\n";
                $first = false;

                continue;
            }

            $stream .= "0 -{$lineGap} Td\n";
            $stream .= "/{$font} {$fontSize} Tf\n";
            $stream .= "({$line}) Tj\n";
        }

        $stream .= "ET";

        return $stream;
    }

    /**
     * @param  array<int, array{font:string, size:int, text:string}>  $headerRows
     * @param  array<int, array{font:string, size:int, text:string}>  $bodyRows
     * @return array<int, array<int, array{font:string, size:int, text:string}>>
     */
    private function buildPages(array $headerRows, array $bodyRows): array
    {
        $maxHeight = 700;
        $pages = [];
        $currentPage = $headerRows;
        $consumedHeight = $this->calculateConsumedHeight($currentPage);
        $headerCount = count($headerRows);

        foreach ($bodyRows as $row) {
            $lineGap = $this->lineGapForSize($row['size']);
            if (($consumedHeight + $lineGap) > $maxHeight && count($currentPage) > $headerCount) {
                $pages[] = $currentPage;
                $currentPage = $headerRows;
                $consumedHeight = $this->calculateConsumedHeight($currentPage);
            }

            $currentPage[] = $row;
            $consumedHeight += $lineGap;
        }

        if (count($currentPage) > $headerCount || $pages === []) {
            $pages[] = $currentPage;
        }

        return $pages;
    }

    /**
     * @param  array<int, array{font:string, size:int, text:string}>  $rows
     */
    private function calculateConsumedHeight(array $rows): int
    {
        if (count($rows) <= 1) {
            return 0;
        }

        $height = 0;
        foreach (array_slice($rows, 1) as $row) {
            $height += $this->lineGapForSize($row['size']);
        }

        return $height;
    }

    private function lineGapForSize(int $size): int
    {
        return $size >= 18 ? 26 : ($size >= 14 ? 20 : 16);
    }

    /**
     * @param  array<int, string>  $objects
     */
    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        ksort($objects);

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $totalObjects = count($objects) + 1;

        $pdf .= "xref\n";
        $pdf .= "0 {$totalObjects}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id < $totalObjects; $id++) {
            $offset = $offsets[$id] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n";
        $pdf .= "<< /Size {$totalObjects} /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= "{$xrefOffset}\n";
        $pdf .= "%%EOF";

        return $pdf;
    }

    private function toPdfString(string $value): string
    {
        $latin1 = iconv('UTF-8', 'windows-1252//IGNORE', $value);
        $safe = $latin1 !== false ? $latin1 : $value;

        return str_replace(
            ['\\', '(', ')', "\r", "\n", "\t"],
            ['\\\\', '\(', '\)', ' ', ' ', ' '],
            $safe
        );
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text): array
    {
        $text = preg_replace("/\r\n|\r/u", "\n", $text) ?? $text;
        $paragraphs = preg_split("/\n{2,}/u", $text) ?: [$text];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                $lines[] = '';

                continue;
            }

            $wrapped = wordwrap(preg_replace('/\s+/u', ' ', $paragraph) ?? $paragraph, 78, "\n", true);

            foreach (explode("\n", $wrapped) as $line) {
                $lines[] = $line;
            }

            $lines[] = '';
        }

        while ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }

    private function buildFilename(ReadingPassage $passage): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', trim((string) $passage->title)) ?? '';
        $slug = strtolower(trim($slug, '-'));
        $slug = $slug !== '' ? $slug : 'lectura';

        return "{$slug}.pdf";
    }
}
