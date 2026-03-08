<?php

namespace App\Enums;

enum UserRole: string
{
    case SOPORTE = 'soporte';
    case DIRECTIVO = 'directivo';
    case DOCENTE = 'docente';

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::SOPORTE, self::DIRECTIVO, self::DOCENTE]);
    }

    /**
     * Check if user can view all attendances.
     */
    public function canViewAllAttendances(): bool
    {
        return in_array($this, [self::SOPORTE, self::DIRECTIVO]);
    }

    /**
     * Check if user can manage users.
     */
    public function canManageUsers(): bool
    {
        return $this === self::SOPORTE;
    }

    /**
     * Check if user can manage campuses.
     */
    public function canManageCampuses(): bool
    {
        return $this === self::SOPORTE;
    }

    /**
     * Check if user can manage schedules.
     */
    public function canManageSchedules(): bool
    {
        return $this === self::SOPORTE;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SOPORTE => 'Soporte',
            self::DIRECTIVO => 'Directivo',
            self::DOCENTE => 'Docente',
        };
    }

    /**
     * Get color for UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::SOPORTE => 'danger',
            self::DIRECTIVO => 'warning',
            self::DOCENTE => 'primary',
        };
    }

    /**
     * Get all roles as array for select options.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($role) => [
            $role->value => $role->label()
        ])->toArray();
    }
}
