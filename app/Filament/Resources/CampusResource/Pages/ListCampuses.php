<?php

namespace App\Filament\Resources\CampusResource\Pages;

use App\Filament\Resources\CampusResource;
use App\Services\QRGeneratorService;
use Filament\Resources\Pages\ListRecords;

class ListCampuses extends ListRecords
{
    protected static string $resource = CampusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
