<?php

namespace Tests\Unit;

use App\Support\GoogleDriveHelper;
use Tests\TestCase;

class GoogleDriveHelperTest extends TestCase
{
    public function test_it_normalizes_category_folder_names_to_upper_snake_case(): void
    {
        $this->assertSame('ACTAS_DE_EXAMEN', GoogleDriveHelper::normalizeCategorySlug('Actas de Examen'));
        $this->assertSame('SIN_CLASIFICAR', GoogleDriveHelper::normalizeCategorySlug(null));
    }

    public function test_it_normalizes_entity_folder_names_to_upper_snake_case(): void
    {
        $this->assertSame('SECRETARIA_ACADEMICA', GoogleDriveHelper::normalizeEntityFolderName('Secretaría Académica'));
        $this->assertSame('SIN_ENTIDAD', GoogleDriveHelper::normalizeEntityFolderName(''));
    }

    public function test_it_normalizes_escaped_newlines_in_private_key_values(): void
    {
        $input = '-----BEGIN PRIVATE KEY-----\\nABCDEF\\n-----END PRIVATE KEY-----\\n';
        $normalized = GoogleDriveHelper::normalizePrivateKey($input);

        $this->assertStringContainsString("-----BEGIN PRIVATE KEY-----\nABCDEF\n-----END PRIVATE KEY-----", $normalized);
        $this->assertStringNotContainsString('\\n', $normalized);
    }

    public function test_it_rebuilds_single_line_private_key_values(): void
    {
        $body = str_repeat('A', 80);
        $input = "-----BEGIN PRIVATE KEY-----{$body}-----END PRIVATE KEY-----";
        $normalized = GoogleDriveHelper::normalizePrivateKey($input);

        $this->assertStringStartsWith("-----BEGIN PRIVATE KEY-----\n", $normalized);
        $this->assertStringContainsString("\n-----END PRIVATE KEY-----\n", $normalized);
        $this->assertGreaterThanOrEqual(3, substr_count($normalized, "\n"));
    }
}
