<?php

namespace App\Filament\Resources\EcosystemApps\Pages;

use App\Filament\Resources\EcosystemApps\EcosystemAppResource;
use App\Models\OAuthClient;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageEcosystemApps extends ManageRecords
{
    protected static string $resource = EcosystemAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva app ecosistema')
                ->createAnother(false)
                ->using(fn (array $data): OAuthClient => EcosystemAppResource::createApp($data))
                ->after(function (CreateAction $action): void {
                    $record = $action->getRecord();

                    if (! $record instanceof OAuthClient) {
                        return;
                    }

                    $secret = (string) $record->plainSecret;

                    Notification::make()
                        ->title('App ecosistema creada')
                        ->body("Client ID: {$record->getKey()}\nClient Secret: {$secret}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
