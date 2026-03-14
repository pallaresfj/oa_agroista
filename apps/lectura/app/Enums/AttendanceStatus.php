<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case ON_TIME = 'on_time';
    case LATE = 'late';
    case ABSENT = 'absent';
    case JUSTIFIED = 'justified';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ON_TIME => 'A tiempo',
            self::LATE => 'Retardo',
            self::ABSENT => 'Falta',
            self::JUSTIFIED => 'Justificado',
        };
    }

    /**
     * Get color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::ON_TIME => 'success',
            self::LATE => 'warning',
            self::ABSENT => 'danger',
            self::JUSTIFIED => 'info',
        };
    }

    /**
     * Get hex color for calendar/charts.
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::ON_TIME => '#10b981',
            self::LATE => '#f59e0b',
            self::ABSENT => '#ef4444',
            self::JUSTIFIED => '#3b82f6',
        };
    }

    /**
     * Get icon name for display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ON_TIME => 'heroicon-o-check-circle',
            self::LATE => 'heroicon-o-clock',
            self::ABSENT => 'heroicon-o-x-circle',
            self::JUSTIFIED => 'heroicon-o-document-check',
        };
    }

    /**
     * Check if this status counts as present.
     */
    public function isPresent(): bool
    {
        return in_array($this, [self::ON_TIME, self::LATE, self::JUSTIFIED]);
    }

    /**
     * Get all statuses as array for select options.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($status) => [
            $status->value => $status->label(),
        ])->toArray();
    }
}
