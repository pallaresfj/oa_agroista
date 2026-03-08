<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentAttendancesWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 2;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Últimas Asistencias Registradas';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('campus.name')
                    ->label('Sede')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_in_time')
                    ->label('Hora')
                    ->dateTime('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (AttendanceStatus $state): string => $state->label())
                    ->color(fn (AttendanceStatus $state): string => match ($state) {
                        AttendanceStatus::ON_TIME => 'success',
                        AttendanceStatus::LATE => 'warning',
                        AttendanceStatus::JUSTIFIED => 'info',
                        AttendanceStatus::ABSENT => 'danger',
                    }),
            ])
            ->defaultSort('check_in_time', 'desc')
            ->paginated([5, 10, 25]);
    }

    protected function getTableQuery(): Builder
    {
        return Attendance::query()
            ->with(['user', 'campus'])
            ->latest('check_in_time')
            ->limit(10);
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->isSoporte();
    }
}
