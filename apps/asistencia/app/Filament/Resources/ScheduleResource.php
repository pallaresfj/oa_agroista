<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Campus;
use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Horarios';

    protected static ?string $modelLabel = 'Horario';

    protected static ?string $pluralModelLabel = 'Horarios';

    protected static ?int $navigationSort = 3;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isSoporte();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación')
                    ->schema([
                        Select::make('user_id')
                            ->label('Docente')
                            ->options(
                                User::query()
                                    ->where('is_active', true)
                                    ->where(function ($query) {
                                        $query->withRole(UserRole::DOCENTE)
                                            ->orWhere(fn ($innerQuery) => $innerQuery->withRole(UserRole::DIRECTIVO));
                                    })
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('campus_id')
                            ->label('Sede')
                            ->options(Campus::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('day_of_week')
                            ->label('Día de la Semana')
                            ->options(Schedule::DAYS)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(1),

                Section::make('Horario')
                    ->schema([
                        TimePicker::make('check_in_time')
                            ->label('Hora de Entrada')
                            ->required()
                            ->seconds(false),

                        TimePicker::make('check_out_time')
                            ->label('Hora de Salida')
                            ->seconds(false),

                        TextInput::make('tolerance_minutes')
                            ->label('Tolerancia')
                            ->required()
                            ->numeric()
                            ->default(15)
                            ->minValue(0)
                            ->maxValue(60)
                            ->suffix('minutos'),

                        Toggle::make('is_active')
                            ->label('Horario Activo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('user.name')
                    ->label('Docente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('campus.name')
                    ->label('Sede')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('day_name')
                    ->label('Día')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('check_in_time')
                    ->label('Entrada')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('check_out_time')
                    ->label('Salida')
                    ->time('H:i'),

                TextColumn::make('tolerance_minutes')
                    ->label('Tolerancia')
                    ->suffix(' min')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Docente')
                    ->options(
                        User::query()
                            ->where(function ($query) {
                                $query->withRole(UserRole::DOCENTE)
                                    ->orWhere(fn ($innerQuery) => $innerQuery->withRole(UserRole::DIRECTIVO));
                            })
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('campus_id')
                    ->label('Sede')
                    ->options(Campus::pluck('name', 'id')),

                SelectFilter::make('day_of_week')
                    ->label('Día')
                    ->options(Schedule::DAYS),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->native(false),
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Editar'),
                DeleteAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Eliminar'),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('day_of_week');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
