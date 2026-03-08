<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceCalendarWidget;
use App\Filament\Widgets\AttendanceScannerWidget;
use App\Filament\Widgets\PersonalStatsWidget;
use App\Filament\Widgets\TodayScheduleWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class DocenteDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationLabel = 'Dashboard Docente';
    
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isDocente();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Dashboard Docente';
    }

    public function getWidgets(): array
    {
        return [
            TodayScheduleWidget::class,
            AttendanceScannerWidget::class,
            PersonalStatsWidget::class,
            AttendanceCalendarWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make($this->getColumns())
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
            ]);
    }
}
