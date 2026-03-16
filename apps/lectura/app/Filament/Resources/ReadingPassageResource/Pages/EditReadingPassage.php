<?php

namespace App\Filament\Resources\ReadingPassageResource\Pages;

use App\Filament\Resources\ReadingPassageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReadingPassage extends EditRecord
{
    protected static string $resource = ReadingPassageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
