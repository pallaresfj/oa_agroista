<?php

namespace App\Filament\Resources\NonWorkingDays\Pages;

use App\Filament\Resources\NonWorkingDays\NonWorkingDayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNonWorkingDay extends CreateRecord
{
    protected static string $resource = NonWorkingDayResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
