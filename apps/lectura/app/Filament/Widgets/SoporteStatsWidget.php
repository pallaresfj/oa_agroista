<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SoporteStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $activeUsers = User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->withRole(UserRole::DOCENTE)
                    ->orWhere(fn ($innerQuery) => $innerQuery->withRole(UserRole::DIRECTIVO));
            })
            ->count();

        $inactiveUsers = User::query()
            ->where('is_active', false)
            ->where(function ($query) {
                $query->withRole(UserRole::DOCENTE)
                    ->orWhere(fn ($innerQuery) => $innerQuery->withRole(UserRole::DIRECTIVO));
            })
            ->count();

        $activeCampus = Campus::where('is_active', true)->count();
        $inactiveCampus = Campus::where('is_active', false)->count();

        return [
            Stat::make('Usuarios Activos', $activeUsers)
                ->description('Docentes y directivos')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Usuarios Inactivos', $inactiveUsers)
                ->description('Deshabilitados')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger'),

            Stat::make('Sedes Activas', $activeCampus)
                ->description('En funcionamiento')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Sedes Inactivas', $inactiveCampus)
                ->description('Deshabilitadas')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->isSoporte();
    }
}
