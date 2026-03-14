<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationLabel = 'Asistencias';

    protected static ?string $modelLabel = 'Asistencia';

    protected static ?string $pluralModelLabel = 'Asistencias';

    protected static ?int $navigationSort = 4;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de Asistencia')
                    ->schema([
                        TextInput::make('check_in_time')
                            ->label('Hora de Ingreso')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status')
                            ->label('Estado')
                            ->options(function (Get $get) {
                                $currentStatus = $get('status');

                                // Si es LATE o JUSTIFIED, puede cambiar entre ambos
                                if (in_array($currentStatus, ['late', 'justified'])) {
                                    return [
                                        AttendanceStatus::LATE->value => AttendanceStatus::LATE->label(),
                                        AttendanceStatus::JUSTIFIED->value => AttendanceStatus::JUSTIFIED->label(),
                                    ];
                                }

                                // Si es ON_TIME o cualquier otro, mostrar LATE y JUSTIFIED
                                return [
                                    AttendanceStatus::LATE->value => AttendanceStatus::LATE->label(),
                                    AttendanceStatus::JUSTIFIED->value => AttendanceStatus::JUSTIFIED->label(),
                                ];
                            })
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('user.name')
                    ->label('DOCENTE')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('campus.name')
                    ->label('SEDE')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('check_in_time')
                    ->label('ENTRADA')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('distance_from_campus')
                    ->label('DISTANCIA')
                    ->suffix(' m')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('ESTADO')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->color(fn ($state) => $state?->color()),
            ])
            ->filters([
                SelectFilter::make('campus_id')
                    ->label('Sede')
                    ->options(Campus::pluck('name', 'id')),

                SelectFilter::make('user_id')
                    ->label('Docente')
                    ->options(User::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(AttendanceStatus::options()),

                Filter::make('check_in_time')
                    ->form([
                        DatePicker::make('from')
                            ->label('Desde'),
                        DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('check_in_time', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('check_in_time', '<=', $data['until']));
                    }),
            ])
            ->recordUrl(
                fn (Attendance $record): ?string => $record->status->value !== 'on_time'
                    ? static::getUrl('edit', ['record' => $record])
                    : null
            )
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->label('Modificar Estado')
                    ->iconButton()
                    ->iconSize('lg')
                    ->tooltip('Editar')
                    ->visible(fn ($record) => $record->status->value !== 'on_time'),
            ])
            ->bulkActions([])
            ->defaultSort('check_in_time', 'desc')
            ->recordClasses(fn ($record) => $record->status->value === 'on_time' ? 'opacity-50' : '');
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
            'index' => Pages\ListAttendances::route('/'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Auth::user();

        return $user && $user->isDirectivo();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
