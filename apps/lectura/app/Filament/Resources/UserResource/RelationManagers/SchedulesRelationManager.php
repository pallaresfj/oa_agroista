<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Campus;
use App\Models\Schedule;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Horarios';

    protected static ?string $modelLabel = 'Horario';

    protected static ?string $pluralModelLabel = 'Horarios';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del Horario')
                    ->schema([
                        Select::make('campus_id')
                            ->label('Sede')
                            ->options(Campus::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Select::make('days_of_week')
                            ->label('Días de la Semana')
                            ->options(Schedule::DAYS)
                            ->required()
                            ->multiple(fn ($context) => $context === 'create')
                            ->native(false)
                            ->columnSpan(1)
                            ->helperText('Seleccione uno o más días para crear el horario')
                            ->visible(fn ($context) => $context === 'create'),

                        Select::make('day_of_week')
                            ->label('Día de la Semana')
                            ->options(Schedule::DAYS)
                            ->required()
                            ->native(false)
                            ->columnSpan(1)
                            ->visible(fn ($context) => $context === 'edit'),

                        TextInput::make('tolerance_minutes')
                            ->label('Tolerancia')
                            ->required()
                            ->numeric()
                            ->default(5)
                            ->minValue(0)
                            ->maxValue(60)
                            ->suffix('minutos')
                            ->columnSpan(1),

                        TimePicker::make('check_in_time')
                            ->label('Hora de Entrada')
                            ->required()
                            ->seconds(false)
                            ->columnSpan(1),

                        TimePicker::make('check_out_time')
                            ->label('Hora de Salida')
                            ->seconds(false)
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Horario Activo')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Check if times overlap.
     */
    protected function timesOverlap(string $start1, ?string $end1, string $start2, ?string $end2): bool
    {
        $toMinutes = fn (string $time): int => ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);

        $start1Min = $toMinutes($start1);
        $end1Min = $end1 ? $toMinutes($end1) : $start1Min + 60;
        $start2Min = $toMinutes($start2);
        $end2Min = $end2 ? $toMinutes($end2) : $start2Min + 60;

        return $start1Min < $end2Min && $start2Min < $end1Min;
    }

    /**
     * Validate no overlapping schedules exist.
     */
    protected function validateNoOverlap(array $data, ?Schedule $record = null): ?string
    {
        $userId = $this->getOwnerRecord()->id;
        $dayOfWeek = $data['day_of_week'] ?? null;
        $checkInTime = $data['check_in_time'] ?? null;
        $checkOutTime = $data['check_out_time'] ?? null;

        if (! $userId || $dayOfWeek === null || ! $checkInTime) {
            return null;
        }

        // Normalize time format
        $normalizeTime = function ($time): string {
            if ($time instanceof \DateTimeInterface) {
                return $time->format('H:i');
            }
            if (is_string($time) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                return substr($time, 0, 5);
            }
            $parsed = date_create($time);

            return $parsed ? $parsed->format('H:i') : (string) $time;
        };

        $checkInTime = $normalizeTime($checkInTime);
        $checkOutTime = $checkOutTime ? $normalizeTime($checkOutTime) : null;

        $query = Schedule::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true);

        if ($record) {
            $query->where('id', '!=', $record->id);
        }

        foreach ($query->get() as $schedule) {
            $existingIn = $schedule->check_in_time->format('H:i');
            $existingOut = $schedule->check_out_time?->format('H:i');

            if ($this->timesOverlap($checkInTime, $checkOutTime, $existingIn, $existingOut)) {
                $dayName = Schedule::DAYS[$dayOfWeek] ?? $dayOfWeek;

                return "Ya existe un horario activo el día {$dayName} que se superpone ({$existingIn}".($existingOut ? " - {$existingOut}" : '').').';
            }
        }

        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->recordTitleAttribute('day_name')
            ->columns([
                TextColumn::make('campus.name')
                    ->label('SEDE')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('day_name')
                    ->label('DÍA')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('check_in_time')
                    ->label('ENTRADA')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('check_out_time')
                    ->label('SALIDA')
                    ->time('H:i'),

                TextColumn::make('tolerance_minutes')
                    ->label('TOLERANCIA')
                    ->suffix(' min')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('ACTIVO')
                    ->boolean(),
            ])
            ->filters([
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
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar Horario')
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data): array {
                        // Guardar los días seleccionados para usar después
                        $data['_selected_days'] = $data['days_of_week'] ?? [];
                        // Establecer el primer día para la validación inicial
                        $data['day_of_week'] = is_array($data['days_of_week']) ? $data['days_of_week'][0] : $data['days_of_week'];
                        unset($data['days_of_week']);

                        return $data;
                    })
                    ->before(function (CreateAction $action, array $data) {
                        // Validar que no haya solapamientos en ninguno de los días seleccionados
                        $selectedDays = $data['_selected_days'] ?? [$data['day_of_week']];

                        foreach ($selectedDays as $day) {
                            $testData = $data;
                            $testData['day_of_week'] = $day;
                            $error = $this->validateNoOverlap($testData);
                            if ($error) {
                                Notification::make()
                                    ->title('Error de validación')
                                    ->body($error)
                                    ->danger()
                                    ->send();
                                $action->halt();

                                return;
                            }
                        }
                    })
                    ->after(function (CreateAction $action, array $data, $record) {
                        // Crear horarios adicionales para los demás días seleccionados
                        $selectedDays = $data['_selected_days'] ?? [];

                        if (count($selectedDays) > 1) {
                            $userId = $this->getOwnerRecord()->id;
                            $createdCount = 0;

                            foreach ($selectedDays as $day) {
                                // Saltar el primer día ya que ya fue creado
                                if ($day == $record->day_of_week) {
                                    continue;
                                }

                                Schedule::create([
                                    'user_id' => $userId,
                                    'campus_id' => $data['campus_id'],
                                    'day_of_week' => $day,
                                    'check_in_time' => $data['check_in_time'],
                                    'check_out_time' => $data['check_out_time'],
                                    'tolerance_minutes' => $data['tolerance_minutes'],
                                    'is_active' => $data['is_active'] ?? true,
                                ]);

                                $createdCount++;
                            }

                            if ($createdCount > 0) {
                                Notification::make()
                                    ->title('Horarios creados')
                                    ->body('Se crearon '.($createdCount + 1).' horarios exitosamente.')
                                    ->success()
                                    ->send();
                            }
                        }
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Editar')
                    ->before(function (EditAction $action, array $data, Schedule $record) {
                        $error = $this->validateNoOverlap($data, $record);
                        if ($error) {
                            Notification::make()
                                ->title('Error de validación')
                                ->body($error)
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    }),
                DeleteAction::make()
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Eliminar'),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('day_of_week')
            ->emptyStateHeading('Sin horarios asignados')
            ->emptyStateDescription('Agregue horarios para este usuario usando el botón de arriba.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
