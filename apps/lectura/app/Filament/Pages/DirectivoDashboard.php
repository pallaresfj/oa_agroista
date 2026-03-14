<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AbsentUsersWidget;
use App\Filament\Widgets\AttendanceCalendarWidget;
use App\Filament\Widgets\AttendanceScannerWidget;
use App\Filament\Widgets\CampusSummaryWidget;
use App\Filament\Widgets\GlobalStatsWidget;
use App\Filament\Widgets\PersonalStatsWidget;
use App\Filament\Widgets\TodayScheduleWidget;
use App\Filament\Widgets\UserSummaryTableWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class DirectivoDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Dashboard Directivo';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Directivo';
    }

    public function getWidgets(): array
    {
        return [
            GlobalStatsWidget::class,
            CampusSummaryWidget::class,
            AbsentUsersWidget::class,
            UserSummaryTableWidget::class,
        ];
    }

    public function getPersonalWidgets(): array
    {
        return [
            TodayScheduleWidget::class,
            AttendanceScannerWidget::class,
            PersonalStatsWidget::class,
            AttendanceCalendarWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mi Asistencia')
                    ->description('Registro y seguimiento de tu asistencia')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getPersonalWidgets())),
                    ]),

                Section::make('Estadísticas Globales')
                    ->description('Vista general de asistencias del período actual')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
                    ]),
            ]);
    }
}
