<?php

namespace App\Filament\Resources\PlanResource\Pages;

use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

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
    protected function resolveRecord($key): Model
    {
        return parent::resolveRecord($key)->loadMissing('schoolProfile');
    }
}
