<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TodayScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.today-schedule-widget';

    protected int|string|array $columnSpan = 'full';

    public function getSchedule()
    {
        $today = now()->dayOfWeek;

        return Schedule::where('user_id', Auth::id())
            ->where('day_of_week', $today)
            ->where('is_active', true)
            ->with('campus')
            ->first();
    }

    public static function canView(): bool
    {
        return Auth::check() && (Auth::user()->isDocente() || Auth::user()->isDirectivo());
    }
}
