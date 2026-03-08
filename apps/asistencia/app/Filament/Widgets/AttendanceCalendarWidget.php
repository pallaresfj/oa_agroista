<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\NonWorkingDay;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AttendanceCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.attendance-calendar-widget';
    
    protected int | string | array $columnSpan = 'full';

    public string $selectedMonth;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
    }

    public function getCalendarData(): array
    {
        $startDate = Carbon::parse($this->selectedMonth)->startOfMonth();
        $endDate = Carbon::parse($this->selectedMonth)->endOfMonth();
        $user = Auth::user();

        // Obtener los días de la semana en los que el usuario tiene horarios activos
        $scheduledDays = Schedule::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('day_of_week')
            ->unique()
            ->toArray();

        // Obtener las asistencias del mes
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        // Obtener días no laborables del mes
        $nonWorkingDays = NonWorkingDay::getInRange($startDate, $endDate);
        $nonWorkingDates = $nonWorkingDays->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();

        $calendar = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $attendance = $attendances->get($dateKey);
            $dayOfWeek = $current->dayOfWeek;
            
            // Verificar si el usuario tiene horario asignado para este día de la semana
            $hasSchedule = in_array($dayOfWeek, $scheduledDays);
            
            // Verificar si es día no laborable
            $isNonWorkingDay = in_array($dateKey, $nonWorkingDates);

            $calendar[$dateKey] = [
                'date' => $current->copy(),
                'day' => $current->day,
                'status' => $attendance?->status ?? null,
                'hasAttendance' => $attendance !== null,
                'hasSchedule' => $hasSchedule,
                'isNonWorkingDay' => $isNonWorkingDay,
            ];

            $current->addDay();
        }

        return $calendar;
    }

    public function previousMonth(): void
    {
        $this->selectedMonth = Carbon::parse($this->selectedMonth)->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->selectedMonth = Carbon::parse($this->selectedMonth)->addMonth()->format('Y-m');
    }

    public static function canView(): bool
    {
        return Auth::check() && (Auth::user()->isDocente() || Auth::user()->isDirectivo());
    }
}
