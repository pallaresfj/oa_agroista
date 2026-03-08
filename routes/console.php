<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| Here you may define all of your scheduled commands. The closure will
| be called with the schedule instance allowed you to fluently define
| your commands.
|
*/

// Generar registros de ausencia automáticamente
// Solo se ejecuta si ABSENCE_TRACKING_ENABLED=true en .env
Schedule::command('attendance:generate-absences')
    ->dailyAt(config('attendance.absence_generation_hour', 23) . ':00')
    ->when(fn () => config('attendance.absence_tracking_enabled', false))
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/absences.log'));
