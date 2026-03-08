<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentPlansWidget;
use App\Filament\Widgets\StatsOverview;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;

class AdminDashboard extends Dashboard
{
    protected static bool $isDiscovered = false;

    protected static ?string $title = 'Inicio';

    protected static ?string $navigationLabel = 'Escritorio';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected Width | string | null $maxContentWidth = Width::Full;

    /**
     * @return int | array<string, int | null>
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'xl' => 12,
        ];
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RecentPlansWidget::class,
        ];
    }
}
