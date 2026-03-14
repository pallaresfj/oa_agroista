<?php

namespace App\Enums;

enum ReadingErrorType: string
{
    case OMISION = 'omision';
    case SUSTITUCION = 'sustitucion';
    case INSERCION = 'insercion';
    case VACILACION = 'vacilacion';

    public function label(): string
    {
        return match ($this) {
            self::OMISION => 'Omisión',
            self::SUSTITUCION => 'Sustitución',
            self::INSERCION => 'Inserción',
            self::VACILACION => 'Vacilación',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OMISION => 'danger',
            self::SUSTITUCION => 'warning',
            self::INSERCION => 'info',
            self::VACILACION => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type): array => [
            $type->value => $type->label(),
        ])->all();
    }
}
