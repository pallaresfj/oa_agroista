<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class GlobalStatsWidget extends BaseWidget
{
    public ?int $selectedCampus = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getStats(): array
    {
        $query = Attendance::query();

        // Apply filters
        if ($this->selectedCampus) {
            $query->where('campus_id', $this->selectedCampus);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        } else {
            $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $total = $query->count();
        $onTime = $query->clone()->where('status', AttendanceStatus::ON_TIME)->count();
        $late = $query->clone()->where('status', AttendanceStatus::LATE)->count();
        $justified = $query->clone()->where('status', AttendanceStatus::JUSTIFIED)->count();
        $absent = $query->clone()->where('status', AttendanceStatus::ABSENT)->count();
        $punctuality = $total > 0 ? round(($onTime / $total) * 100, 1) : 0;

        return [
            Stat::make('Total Asistencias', $total)
                ->description('Registros en el período')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            
            Stat::make('A Tiempo', $onTime)
                ->description('Asistencias puntuales')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Retardos', $late)
                ->description('Llegadas tarde')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Justificadas', $justified)
                ->description('Retardos justificados')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('Inasistencias', $absent)
                ->description('Faltas registradas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            
            Stat::make('Puntualidad Global', $punctuality . '%')
                ->description('Porcentaje de puntualidad')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($punctuality >= 90 ? 'success' : ($punctuality >= 75 ? 'warning' : 'danger')),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }
}
