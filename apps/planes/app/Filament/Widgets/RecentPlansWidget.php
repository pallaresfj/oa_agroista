<?php

namespace App\Filament\Widgets;

use App\Models\Plan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPlansWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ultimos planes actualizados')
            ->query($this->getRecentPlansQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('year')
                    ->label('Ano')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (Plan $record): string => $record->updated_at?->diffForHumans() ?? 'Sin fecha')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated(false);
    }

    protected function getRecentPlansQuery(): Builder
    {
        return Plan::query()
            ->latest('updated_at')
            ->limit(10);
    }
}
