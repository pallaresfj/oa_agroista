<?php

namespace App\Filament\Resources\NonWorkingDays\Schemas;

use App\Models\NonWorkingDay;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NonWorkingDayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del día no laborable')
                    ->description('Configure los días festivos, vacaciones o días especiales')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Día del Trabajo, Semana Santa'),

                        Select::make('type')
                            ->label('Tipo')
                            ->options(NonWorkingDay::getTypeLabels())
                            ->required()
                            ->default(NonWorkingDay::TYPE_HOLIDAY),

                        Select::make('campus_id')
                            ->label('Sede')
                            ->relationship('campus', 'name')
                            ->placeholder('Todas las sedes')
                            ->helperText('Deje vacío para aplicar a todas las sedes')
                            ->searchable()
                            ->preload(),

                        Toggle::make('is_recurring')
                            ->label('Recurrente anualmente')
                            ->helperText('Marque si este día se repite cada año (ej: 1 de enero)')
                            ->default(false),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Notas adicionales sobre este día no laborable'),
                    ])
                    ->columns(2),
            ]);
    }
}
