<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Support\GoogleWorkspace\Contracts\WorkspaceUserDirectory;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected bool $workspaceValidationEnabled = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->workspaceValidationEnabled = (bool) config('services.google.validate_users_on_create', true);

        if ($this->workspaceValidationEnabled) {
            $this->assertUserExistsInWorkspace((string) ($data['email'] ?? ''));
        }

        $data['password'] = null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->url(static::getResource()::getUrl('index'))
            ->color('gray');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $notification = Notification::make()
            ->success()
            ->title('Usuario creado correctamente');

        if ($this->workspaceValidationEnabled) {
            $notification->body('El correo fue validado en Google Workspace.');
        } else {
            $notification->body('La validación de Google Workspace está desactivada.');
        }

        return $notification;
    }

    protected function assertUserExistsInWorkspace(string $email): void
    {
        try {
            $exists = app(WorkspaceUserDirectory::class)->userExists($email);
        } catch (\RuntimeException $exception) {
            $this->failWithEmailValidation($exception->getMessage());
        }

        if (! $exists) {
            $this->failWithEmailValidation('El correo no existe en Google Workspace.');
        }
    }

    protected function failWithEmailValidation(string $message): void
    {
        Notification::make()
            ->danger()
            ->title('No se pudo crear el usuario')
            ->body($message)
            ->persistent()
            ->send();

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }
}
