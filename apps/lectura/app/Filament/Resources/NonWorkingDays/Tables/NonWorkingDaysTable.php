<?php

namespace App\Filament\Resources\NonWorkingDays\Tables;

use App\Models\NonWorkingDay;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NonWorkingDaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => NonWorkingDay::getTypeLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'holiday' => 'success',
                        'vacation' => 'info',
                        'special' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('campus.name')
                    ->label('Sede')
                    ->placeholder('Todas')
                    ->sortable(),

                IconColumn::make('is_recurring')
                    ->label('Recurrente')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(NonWorkingDay::getTypeLabels()),

                SelectFilter::make('campus_id')
                    ->label('Sede')
                    ->relationship('campus', 'name')
                    ->placeholder('Todas las sedes'),

                TernaryFilter::make('is_recurring')
                    ->label('Recurrente')
                    ->placeholder('Todos')
                    ->trueLabel('Solo recurrentes')
                    ->falseLabel('Solo no recurrentes'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
