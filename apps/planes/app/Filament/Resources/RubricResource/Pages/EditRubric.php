<?php

namespace App\Filament\Resources\RubricResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\RubricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRubric extends EditRecord
{
    protected static string $resource = RubricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
