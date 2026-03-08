<?php

namespace App\Filament\Resources\SchoolProfileResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SchoolProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolProfiles extends ManageRecords
{
    protected static string $resource = SchoolProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
