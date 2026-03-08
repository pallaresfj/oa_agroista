<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AbsentUsersWidget extends Widget
{
    protected string $view = 'filament.widgets.absent-users-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function getAbsentUsers()
    {
        return app(ReportService::class)->generateAbsenceReport(today());
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }
}
