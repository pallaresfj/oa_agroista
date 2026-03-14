<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AttendanceScannerWidget extends Widget
{
    protected string $view = 'filament.widgets.attendance-scanner-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check() && (Auth::user()->isDocente() || Auth::user()->isDirectivo());
    }
}
