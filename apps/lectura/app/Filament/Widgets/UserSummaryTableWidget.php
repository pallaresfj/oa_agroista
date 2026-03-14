<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserSummaryTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    protected static ?string $heading = 'Resumen por Docente';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('DOCENTE')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('TOTAL')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('on_time_count')
                    ->label('A TIEMPO')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('late_count')
                    ->label('RETARDOS')
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('justified_count')
                    ->label('JUSTIFICADAS')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('absent_count')
                    ->label('INASISTENCIAS')
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('punctuality')
                    ->label('PUNTUALIDAD')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        if ($record->attendances_count == 0) {
                            return 0;
                        }

                        return round(($record->on_time_count / $record->attendances_count) * 100, 1);
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 75 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('name', 'asc');
    }

    protected function getTableQuery(): Builder
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        return User::query()
            ->withRole(UserRole::DOCENTE)
            ->where('is_active', true)
            ->withCount([
                'attendances' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                },
                'attendances as on_time_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'on_time')
                        ->whereBetween('date', [$startDate, $endDate]);
                },
                'attendances as late_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'late')
                        ->whereBetween('date', [$startDate, $endDate]);
                },
                'attendances as justified_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'justified')
                        ->whereBetween('date', [$startDate, $endDate]);
                },
                'attendances as absent_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'absent')
                        ->whereBetween('date', [$startDate, $endDate]);
                },
            ])
            ->having('attendances_count', '>', 0);
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }
}
