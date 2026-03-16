<?php

namespace App\Filament\Widgets;

use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\Student;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ReadingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $attemptsQuery = ReadingAttempt::query()->where('status', ReadingAttempt::STATUS_COMPLETED);

        if (! $user?->can('view_any_reading_attempt')) {
            $attemptsQuery->where('teacher_id', Auth::id());
        }

        $studentsQuery = Student::query();

        if (! $user?->can('view_any_student')) {
            $courseIds = $user?->assignedCourses()->pluck('courses.id')->all() ?? [];

            if ($courseIds === []) {
                $studentsQuery->whereRaw('1 = 0');
            } else {
                $studentsQuery->whereIn('course_id', $courseIds);
            }
        }

        $completedAttempts = (clone $attemptsQuery)->count();
        $averageWpm = round((float) ((clone $attemptsQuery)->avg('words_per_minute') ?? 0), 1);

        return [
            Stat::make('Estudiantes', (clone $studentsQuery)->count())
                ->description('Registrados en el sistema')
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->color('primary'),
            Stat::make('Lecturas activas', ReadingPassage::query()->where('is_active', true)->count())
                ->description('Banco listo para usar')
                ->descriptionIcon('heroicon-m-book-open', IconPosition::Before)
                ->color('info'),
            Stat::make('Intentos completados', $completedAttempts)
                ->description('Resultados guardados')
                ->descriptionIcon('heroicon-m-check-circle', IconPosition::Before)
                ->color('success'),
            Stat::make('Promedio WPM', number_format($averageWpm, 1))
                ->description('Velocidad lectora media')
                ->descriptionIcon('heroicon-m-bolt', IconPosition::Before)
                ->color($averageWpm >= 100 ? 'success' : ($averageWpm >= 60 ? 'warning' : 'danger')),
        ];
    }
}
