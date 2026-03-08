<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\Center;
use App\Models\Plan;
use App\Models\Rubric;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Topic;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Estadisticas e indicadores';

    /**
     * @var int | array<string, int | null> | null
     */
    protected int | array | null $columns = [
        'default' => 1,
        'lg' => 3,
    ];

    protected function getStats(): array
    {
        $centers = Center::query()->count();
        $plans = Plan::query()->count();
        $subjects = Subject::query()->count();
        $topics = Topic::query()->count();
        $rubrics = Rubric::query()->count();
        $teachers = Teacher::query()->count();
        $students = Student::query()->count();
        $activities = Activity::query()->count();
        $recentPlans = Plan::query()
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        return [
            Stat::make('Centros de interes', $centers)
                ->description('Centros activos en la plataforma')
                ->color('success'),
            Stat::make('Planes de area', $plans)
                ->description('Planes vigentes registrados')
                ->color('primary'),
            Stat::make('Asignaturas', $subjects)
                ->description('Asignaturas gestionadas')
                ->color('info'),
            Stat::make('Temas', $topics)
                ->description('Contenidos curriculares cargados')
                ->color('warning'),
            Stat::make('Rubricas', $rubrics)
                ->description('Rubricas de evaluacion disponibles')
                ->color('danger'),
            Stat::make('Docentes', $teachers)
                ->description('Docentes vinculados')
                ->color('success'),
            Stat::make('Estudiantes', $students)
                ->description('Estudiantes registrados')
                ->color('info'),
            Stat::make('Actividades', $activities)
                ->description('Actividades planeadas')
                ->color('primary'),
            Stat::make('Planes actualizados (30 dias)', $recentPlans)
                ->description('Cambios recientes en planes de area')
                ->color('success'),
        ];
    }
}
