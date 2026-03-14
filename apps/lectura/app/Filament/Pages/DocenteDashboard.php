<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ReadingStatsWidget;
use App\Filament\Widgets\RecentAttemptsWidget;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class DocenteDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isDocente();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Panel de lectura';
    }

    public function getWidgets(): array
    {
        return [
            ReadingStatsWidget::class,
            RecentAttemptsWidget::class,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
            ]);
    }
}
