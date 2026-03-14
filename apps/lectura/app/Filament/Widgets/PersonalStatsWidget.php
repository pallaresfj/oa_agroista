<?php

namespace App\Filament\Widgets;

use App\Services\AttendanceService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PersonalStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $attendanceService = app(AttendanceService::class);
        $stats = $attendanceService->getUserStats(Auth::user());

        return [
            Stat::make('Total de Asistencias', $stats['total'])
                ->description('Registros totales')
                ->descriptionIcon('heroicon-m-calendar', IconPosition::Before)
                ->color('primary'),

            Stat::make('A Tiempo', $stats['on_time'])
                ->description('Asistencias puntuales')
                ->descriptionIcon('heroicon-m-check-circle', IconPosition::Before)
                ->color('success'),

            Stat::make('Retardos', $stats['late'])
                ->description('Llegadas tarde')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color('warning'),

            Stat::make('Justificadas', $stats['justified'] ?? 0)
                ->description('Retardos justificados')
                ->descriptionIcon('heroicon-m-document-check', IconPosition::Before)
                ->color('info'),

            Stat::make('Inasistencias', $stats['absent'] ?? 0)
                ->description('Faltas registradas')
                ->descriptionIcon('heroicon-m-x-circle', IconPosition::Before)
                ->color('danger'),

            Stat::make('Puntualidad', number_format($stats['punctuality'], 1).'%')
                ->description('Porcentaje de puntualidad')
                ->descriptionIcon('heroicon-m-chart-bar', IconPosition::Before)
                ->color($stats['punctuality'] >= 90 ? 'success' : ($stats['punctuality'] >= 75 ? 'warning' : 'danger')),
        ];
    }

    public static function canView(): bool
    {
        return Auth::check() && (Auth::user()->isDocente() || Auth::user()->isDirectivo());
    }
}
