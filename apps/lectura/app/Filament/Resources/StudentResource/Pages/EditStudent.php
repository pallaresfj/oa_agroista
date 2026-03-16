<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        if (! StudentResource::canAccess()) {
            return false;
        }

        $recordParam = $parameters['record'] ?? null;

        if ($recordParam instanceof Student) {
            $record = $recordParam;
        } elseif (is_scalar($recordParam) && filled((string) $recordParam)) {
            $record = Student::query()->find($recordParam);
        } else {
            $record = null;
        }

        if (! $record) {
            return false;
        }

        return StudentResource::canEdit($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => StudentResource::canDelete($this->record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
