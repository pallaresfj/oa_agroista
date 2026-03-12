<?php

use App\Filament\Resources\DocumentResource\Pages\Concerns\UploadsToGoogleDrive;

it('returns actionable guidance when service account is not configured', function () {
    $helper = new class
    {
        use UploadsToGoogleDrive;

        public function resolve(\Throwable $e, string $fallback): string
        {
            return $this->resolveDriveUserMessage($e, $fallback);
        }
    };

    $message = $helper->resolve(
        new RuntimeException('Google Drive Service Account not configured.'),
        'fallback'
    );

    expect($message)->toContain('GOOGLE_DRIVE_PRIVATE_KEY');
    expect($message)->toContain('GOOGLE_DRIVE_CLIENT_EMAIL');
    expect($message)->toContain('GOOGLE_DRIVE_CLIENT_ID');
});

it('returns actionable guidance when root folder id is missing', function () {
    $helper = new class
    {
        use UploadsToGoogleDrive;

        public function resolve(\Throwable $e, string $fallback): string
        {
            return $this->resolveDriveUserMessage($e, $fallback);
        }
    };

    $message = $helper->resolve(
        new RuntimeException('GOOGLE_DRIVE_FOLDER_ID is not configured.'),
        'fallback'
    );

    expect($message)->toContain('GOOGLE_DRIVE_FOLDER_ID');
});

it('keeps quota/shared-drive diagnostics from google drive errors', function () {
    $helper = new class
    {
        use UploadsToGoogleDrive;

        public function resolve(\Throwable $e, string $fallback): string
        {
            return $this->resolveDriveUserMessage($e, $fallback);
        }
    };

    $rawMessage = 'Google Drive rechazó la subida: la Service Account no puede escribir en carpetas fuera de Shared Drive.';

    $message = $helper->resolve(
        new RuntimeException($rawMessage),
        'fallback'
    );

    expect($message)->toBe($rawMessage);
});
