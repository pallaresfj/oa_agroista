<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case SOPORTE = 'soporte';
    case DIRECTIVO = 'directivo';
    case DOCENTE = 'docente';

    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::SOPORTE, self::DIRECTIVO, self::DOCENTE], true);
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::SOPORTE], true);
    }

    public function canManageStudents(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::SOPORTE, self::DOCENTE], true);
    }

    public function canManageReadings(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::SOPORTE, self::DOCENTE], true);
    }

    public function canRegisterAttempts(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::SOPORTE, self::DOCENTE], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::SOPORTE => 'Soporte',
            self::DIRECTIVO => 'Directivo',
            self::DOCENTE => 'Docente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'danger',
            self::SOPORTE => 'danger',
            self::DIRECTIVO => 'warning',
            self::DOCENTE => 'primary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($role) => [
            $role->value => $role->label(),
        ])->toArray();
    }
}
