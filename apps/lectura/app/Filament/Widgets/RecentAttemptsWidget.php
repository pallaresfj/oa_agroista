<?php

namespace App\Filament\Widgets;

use App\Models\ReadingAttempt;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentAttemptsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Intentos recientes';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Estudiante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('passage.title')
                    ->label('Lectura')
                    ->limit(28)
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('Tiempo')
                    ->formatStateUsing(fn (int $state): string => gmdate('i:s', $state)),
                Tables\Columns\TextColumn::make('words_per_minute')
                    ->label('WPM')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_errors')
                    ->label('Errores')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'success' : ($state <= 3 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Finalizado')
                    ->since(),
            ])
            ->recordUrl(fn (ReadingAttempt $record): string => route('filament.app.resources.reading-attempts.view', ['record' => $record]))
            ->defaultSort('finished_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $query = ReadingAttempt::query()
            ->with(['student', 'passage'])
            ->where('status', ReadingAttempt::STATUS_COMPLETED)
            ->latest('finished_at');

        if (! Auth::user()->isSuperAdmin()) {
            $query->where('teacher_id', Auth::id());
        }

        return $query->limit(10);
    }
}
