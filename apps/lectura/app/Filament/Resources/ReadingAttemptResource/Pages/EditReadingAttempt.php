<?php

namespace App\Filament\Resources\ReadingAttemptResource\Pages;

use App\Filament\Resources\ReadingAttemptResource;
use Filament\Resources\Pages\EditRecord;

class EditReadingAttempt extends EditRecord
{
    protected static string $resource = ReadingAttemptResource::class;

    protected function afterSave(): void
    {
        $this->record->forceFill([
            'total_errors' => $this->record->errors()->count(),
        ])->save();
    }
}

