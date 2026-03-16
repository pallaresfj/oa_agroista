<?php

namespace App\Filament\Resources\ReadingAttemptResource\Pages;

use App\Enums\ReadingErrorType;
use App\Filament\Resources\ReadingAttemptResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class EditReadingAttempt extends EditRecord
{
    protected static string $resource = ReadingAttemptResource::class;

    /**
     * @var array<string, int>
     */
    protected array $errorCounts = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $currentCounts = $this->record->errors()
            ->selectRaw('error_type, COUNT(*) as total')
            ->groupBy('error_type')
            ->pluck('total', 'error_type')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();

        $data['error_counts'] = collect(ReadingErrorType::cases())
            ->mapWithKeys(fn (ReadingErrorType $type): array => [
                $type->value => (int) ($currentCounts[$type->value] ?? 0),
            ])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $formCounts = $data['error_counts'] ?? [];

        $this->errorCounts = collect(ReadingErrorType::cases())
            ->mapWithKeys(fn (ReadingErrorType $type): array => [
                $type->value => max(0, (int) ($formCounts[$type->value] ?? 0)),
            ])
            ->all();

        unset($data['error_counts']);

        return $data;
    }

    protected function afterSave(): void
    {
        foreach (ReadingErrorType::cases() as $type) {
            $this->syncErrorCountForType($type->value, $this->errorCounts[$type->value] ?? 0);
        }

        $this->record->forceFill([
            'total_errors' => $this->record->errors()->count(),
        ])->save();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function syncErrorCountForType(string $errorType, int $desiredCount): void
    {
        /** @var EloquentCollection<int, \App\Models\ReadingError> $existingErrors */
        $existingErrors = $this->record->errors()
            ->where('error_type', $errorType)
            ->orderBy('id')
            ->get();

        $currentCount = $existingErrors->count();

        if ($currentCount > $desiredCount) {
            $idsToDelete = $existingErrors
                ->sortByDesc('id')
                ->take($currentCount - $desiredCount)
                ->pluck('id')
                ->all();

            if ($idsToDelete !== []) {
                $this->record->errors()->whereIn('id', $idsToDelete)->delete();
            }

            return;
        }

        if ($currentCount >= $desiredCount) {
            return;
        }

        $rowsToCreate = [];

        for ($i = 0; $i < $desiredCount - $currentCount; $i++) {
            $rowsToCreate[] = [
                'error_type' => $errorType,
                'occurred_at_seconds' => 0,
                'word_index' => null,
                'comment' => null,
            ];
        }

        $this->record->errors()->createMany($rowsToCreate);
    }
}
