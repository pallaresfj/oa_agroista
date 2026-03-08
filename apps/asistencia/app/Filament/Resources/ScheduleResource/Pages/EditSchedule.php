<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use App\Models\Schedule;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSchedule extends EditRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $error = $this->validateNoOverlap($this->data, $this->record);
        if ($error) {
            Notification::make()
                ->title('Error de validación')
                ->body($error)
                ->danger()
                ->send();
            $this->halt();
        }
    }

    protected function validateNoOverlap(array $data, Schedule $record): ?string
    {
        $userId = $data['user_id'] ?? null;
        $dayOfWeek = $data['day_of_week'] ?? null;
        $checkInTime = $data['check_in_time'] ?? null;
        $checkOutTime = $data['check_out_time'] ?? null;

        if (!$userId || $dayOfWeek === null || !$checkInTime) {
            return null;
        }

        $checkInTime = $this->normalizeTime($checkInTime);
        $checkOutTime = $checkOutTime ? $this->normalizeTime($checkOutTime) : null;

        $existingSchedules = Schedule::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('id', '!=', $record->id)
            ->get();

        foreach ($existingSchedules as $schedule) {
            $existingIn = $schedule->check_in_time->format('H:i');
            $existingOut = $schedule->check_out_time?->format('H:i');

            if ($this->timesOverlap($checkInTime, $checkOutTime, $existingIn, $existingOut)) {
                $dayName = Schedule::DAYS[$dayOfWeek] ?? $dayOfWeek;
                return "Ya existe un horario activo el día {$dayName} que se superpone ({$existingIn}" . ($existingOut ? " - {$existingOut}" : "") . ").";
            }
        }

        return null;
    }

    protected function normalizeTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }
        if (is_string($time) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            return substr($time, 0, 5);
        }
        $parsed = date_create($time);
        return $parsed ? $parsed->format('H:i') : (string) $time;
    }

    protected function timesOverlap(string $start1, ?string $end1, string $start2, ?string $end2): bool
    {
        $toMinutes = fn(string $time): int => ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
        
        $start1Min = $toMinutes($start1);
        $end1Min = $end1 ? $toMinutes($end1) : $start1Min + 60;
        $start2Min = $toMinutes($start2);
        $end2Min = $end2 ? $toMinutes($end2) : $start2Min + 60;

        return $start1Min < $end2Min && $start2Min < $end1Min;
    }
}
