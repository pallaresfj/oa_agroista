<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;
use App\Models\Plan;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Plan
    {
        try {
            return Plan::create($data);
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                Notification::make()
                    ->title('No se pudo guardar el Plan de área')
                    ->body('No se ha establecido el perfil institucional. Asegurese que se ha establecido la misión y visión institucional.')
                    ->danger()
                    ->send();

                $this->halt(); // Detiene la ejecución sin error 500
            }

            throw $e;
        }
    }
}
