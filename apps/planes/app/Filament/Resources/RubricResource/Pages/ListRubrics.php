<?php

namespace App\Filament\Resources\RubricResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\RubricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRubrics extends ListRecords
{
    protected static string $resource = RubricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
