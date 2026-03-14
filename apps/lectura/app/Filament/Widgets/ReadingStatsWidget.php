<?php

namespace App\Filament\Widgets;

use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\Student;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ReadingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $attemptsQuery = ReadingAttempt::query()->where('status', ReadingAttempt::STATUS_COMPLETED);

        if (! Auth::user()->isSuperAdmin()) {
            $attemptsQuery->where('teacher_id', Auth::id());
        }

        $completedAttempts = (clone $attemptsQuery)->count();
        $averageWpm = round((float) ((clone $attemptsQuery)->avg('words_per_minute') ?? 0), 1);

        return [
            Stat::make('Estudiantes activos', Student::query()->where('is_active', true)->count())
                ->description('Disponibles para evaluar')
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->color('primary'),
            Stat::make('Lecturas activas', ReadingPassage::query()->where('is_active', true)->count())
                ->description('Banco listo para usar')
                ->descriptionIcon('heroicon-m-book-open', IconPosition::Before)
                ->color('info'),
            Stat::make('Intentos completados', $completedAttempts)
                ->description('Resultados guardados')
                ->descriptionIcon('heroicon-m-check-circle', IconPosition::Before)
                ->color('success'),
            Stat::make('Promedio WPM', number_format($averageWpm, 1))
                ->description('Velocidad lectora media')
                ->descriptionIcon('heroicon-m-bolt', IconPosition::Before)
                ->color($averageWpm >= 100 ? 'success' : ($averageWpm >= 60 ? 'warning' : 'danger')),
        ];
    }
}
