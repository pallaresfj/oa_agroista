<?php

namespace App\Filament\Resources\RubricResource\Pages;

use App\Filament\Resources\RubricResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRubric extends CreateRecord
{
    protected static string $resource = RubricResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
