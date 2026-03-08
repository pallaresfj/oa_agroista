<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeDashboardWidget extends Widget
{
    protected string $view = 'filament.widgets.welcome-dashboard-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /**
     * @return array<string, string>
     */
    protected function getViewData(): array
    {
        return [
            'userName' => Auth::user()?->name ?? 'Usuario',
        ];
    }
}
