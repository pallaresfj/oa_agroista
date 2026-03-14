<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case DOCENTE = 'docente';
    case ESTUDIANTE = 'estudiante';

    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::DOCENTE], true);
    }

    public function canManageUsers(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    public function canManageStudents(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::DOCENTE], true);
    }

    public function canManageReadings(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::DOCENTE], true);
    }

    public function canRegisterAttempts(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::DOCENTE], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Administrador',
            self::DOCENTE => 'Docente',
            self::ESTUDIANTE => 'Estudiante',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'danger',
            self::DOCENTE => 'primary',
            self::ESTUDIANTE => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($role) => [
            $role->value => $role->label(),
        ])->toArray();
    }
}
