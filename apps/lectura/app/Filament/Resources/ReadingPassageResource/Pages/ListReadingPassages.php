<?php

namespace App\Filament\Resources\ReadingPassageResource\Pages;

use App\Filament\Resources\ReadingPassageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReadingPassages extends ListRecords
{
    protected static string $resource = ReadingPassageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
