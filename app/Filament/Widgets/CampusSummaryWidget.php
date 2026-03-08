<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Campus;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampusSummaryWidget extends Widget
{
    protected string $view = 'filament.widgets.campus-summary-widget';
    
    protected int | string | array $columnSpan = 'full';

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function getCampusSummary()
    {
        $startDate = $this->startDate && $this->endDate 
            ? $this->startDate 
            : now()->startOfMonth();
        $endDate = $this->startDate && $this->endDate 
            ? $this->endDate 
            : now()->endOfMonth();

        // Get attendance stats grouped by campus
        $attendanceStats = Attendance::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                'campus_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'on_time' THEN 1 ELSE 0 END) as on_time"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late"),
                DB::raw("SUM(CASE WHEN status = 'justified' THEN 1 ELSE 0 END) as justified"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent")
            )
            ->groupBy('campus_id')
            ->get()
            ->keyBy('campus_id');

        // Get all active campuses and merge with stats
        return Campus::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($campus) use ($attendanceStats) {
                $stats = $attendanceStats->get($campus->id);
                $total = $stats?->total ?? 0;
                $onTime = $stats?->on_time ?? 0;
                $late = $stats?->late ?? 0;
                $justified = $stats?->justified ?? 0;
                $absent = $stats?->absent ?? 0;
                $punctuality = $total > 0 ? round(($onTime / $total) * 100, 1) : 0;

                return (object) [
                    'campus' => $campus,
                    'total' => $total,
                    'on_time' => $onTime,
                    'late' => $late,
                    'justified' => $justified,
                    'absent' => $absent,
                    'punctuality' => $punctuality,
                ];
            });
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->isDirectivo();
    }
}
