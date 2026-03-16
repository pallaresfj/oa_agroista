<?php

namespace App\Filament\Resources\ReadingPassageResource\Pages;

use App\Filament\Resources\ReadingPassageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReadingPassage extends CreateRecord
{
    protected static string $resource = ReadingPassageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
