<?php

namespace App\Filament\Resources\CampusResource\Pages;

use App\Filament\Resources\CampusResource;
use App\Services\QRGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCampus extends CreateRecord
{
    protected static string $resource = CampusResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate unique QR token
        $data['qr_token'] = Str::random(32);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Generate QR code after campus is created
        $qrService = app(QRGeneratorService::class);
        $qrService->generateCampusQR($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
