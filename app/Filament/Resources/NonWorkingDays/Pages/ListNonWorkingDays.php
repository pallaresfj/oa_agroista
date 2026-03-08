<?php

namespace App\Filament\Resources\NonWorkingDays\Pages;

use App\Filament\Resources\NonWorkingDays\NonWorkingDayResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNonWorkingDays extends ListRecords
{
    protected static string $resource = NonWorkingDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo día'),

            Action::make('createRange')
                ->label('Crear vacaciones (rango)')
                ->icon('heroicon-o-calendar-days')
                ->url(NonWorkingDayResource::getUrl('create-range'))
                ->color('success'),
        ];
    }
}
