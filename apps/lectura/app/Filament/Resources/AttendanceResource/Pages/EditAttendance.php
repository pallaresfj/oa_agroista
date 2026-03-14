<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Bloquear acceso si el estado es "A tiempo"
        if ($this->record->status->value === 'on_time') {
            Notification::make()
                ->title('Acción no permitida')
                ->body('No se pueden editar registros con estado "A tiempo".')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();
        $newStatus = $data['status'] ?? null;
        $notes = trim($data['notes'] ?? '');

        // Si el estado es "Retardo" o "Justificado", la observación es obligatoria
        if (in_array($newStatus, ['late', 'justified'])) {
            if (empty($notes)) {
                Notification::make()
                    ->title('Validación fallida')
                    ->body('Los retardos y registros justificados deben tener una observación.')
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'notes' => 'Los retardos y registros justificados deben tener una observación.',
                ]);
            }
        }
    }
}
