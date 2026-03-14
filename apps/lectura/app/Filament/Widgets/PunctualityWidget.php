<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PunctualityWidget extends Widget
{
    protected string $view = 'filament.widgets.punctuality-widget';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    public function getPunctuality(): float
    {
        $totalAttendances = Attendance::count();
        $onTimeAttendances = Attendance::where('status', 'on_time')->count();

        return $totalAttendances > 0
            ? round(($onTimeAttendances / $totalAttendances) * 100, 1)
            : 0;
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->isSoporte();
    }
}
