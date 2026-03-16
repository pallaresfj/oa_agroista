<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Services\StudentXlsxImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importStudents')
                ->label('Importar Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn (): bool => StudentResource::canCreate())
                ->modalHeading('Importar estudiantes')
                ->modalDescription("Sube un archivo .xlsx con columnas 'curso' y 'nombre'.")
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel (.xlsx)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->rules(['required', 'extensions:xlsx'])
                        ->storeFiles(false)
                        ->visibility('private')
                        ->required(),
                ])
                ->action(function (array $data, StudentXlsxImporter $importer): void {
                    $file = $data['file'] ?? null;

                    if (! $file instanceof TemporaryUploadedFile) {
                        Notification::make()
                            ->title('Importación no iniciada')
                            ->body('Debes seleccionar un archivo .xlsx válido.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $filePath = $file->getRealPath() ?: $file->getPathname();
                        $result = $importer->import($filePath);
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->title('Error al importar estudiantes')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $summary = implode(' | ', [
                        "Filas: {$result['total_data_rows']}",
                        "Creados: {$result['created']}",
                        "Omitidos: {$result['skipped']}",
                        "Fallidos: {$result['failed']}",
                    ]);

                    if ($result['failed'] > 0) {
                        $errorPreview = implode(' || ', array_slice($result['errors'], 0, 3));

                        Notification::make()
                            ->title('Importación completada con observaciones')
                            ->body($summary.($errorPreview !== '' ? " || {$errorPreview}" : ''))
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Importación completada')
                        ->body($summary)
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->visible(fn (): bool => StudentResource::canCreate()),
        ];
    }
}
