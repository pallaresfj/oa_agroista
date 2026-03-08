<?php

namespace App\Filament\Resources\TopicResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTopics extends ListRecords
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
