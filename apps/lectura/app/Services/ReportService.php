<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\NonWorkingDay;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Generate a general attendance report.
     *
     * @param  array  $filters  Optional filters (campus_id, user_id, status)
     */
    public function generateGeneralReport(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $filters = []
    ): Collection {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $query = Attendance::with(['user', 'campus', 'schedule'])
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->orderBy('check_in_time', 'desc');

        // Apply filters
        if (! empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    /**
     * Generate an absence report for a specific date.
     */
    public function generateAbsenceReport(?Carbon $date = null): Collection
    {
        $date = $date ?? today();
        $dayOfWeek = $date->dayOfWeek;

        // Get all users with active schedules on this day
        $usersWithSchedules = User::whereHas('schedules', function ($query) use ($dayOfWeek) {
            $query->where('day_of_week', $dayOfWeek)
                ->where('is_active', true);
        })
            ->where('is_active', true)
            ->get();

        // Get users who registered attendance on this date
        $usersWithAttendance = Attendance::whereDate('check_in_time', $date)
            ->pluck('user_id')
            ->toArray();

        // Filter users who didn't register
        return $usersWithSchedules->filter(function ($user) use ($usersWithAttendance) {
            return ! in_array($user->id, $usersWithAttendance);
        })->sortBy('name')->values();
    }

    /**
     * Get attendance summary by campus.
     */
    public function getSummaryByCampus(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return Campus::withCount([
            'attendances as total_attendances' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in_time', [$startDate, $endDate]);
            },
            'attendances as on_time_count' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in_time', [$startDate, $endDate])
                    ->where('status', AttendanceStatus::ON_TIME->value);
            },
            'attendances as late_count' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in_time', [$startDate, $endDate])
                    ->where('status', AttendanceStatus::LATE->value);
            },
            'attendances as justified_count' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in_time', [$startDate, $endDate])
                    ->where('status', AttendanceStatus::JUSTIFIED->value);
            },
        ])->get();
    }

    /**
     * Get attendance summary by user.
     */
    public function getSummaryByUser(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $campusId = null
    ): Collection {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $query = User::query()
            ->where('is_active', true)
            ->withCount([
                'attendances as total_attendances' => function ($q) use ($startDate, $endDate, $campusId) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate]);
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                },
                'attendances as on_time_count' => function ($q) use ($startDate, $endDate, $campusId) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('status', AttendanceStatus::ON_TIME->value);
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                },
                'attendances as late_count' => function ($q) use ($startDate, $endDate, $campusId) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('status', AttendanceStatus::LATE->value);
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                },
                'attendances as justified_count' => function ($q) use ($startDate, $endDate, $campusId) {
                    $q->whereBetween('check_in_time', [$startDate, $endDate])
                        ->where('status', AttendanceStatus::JUSTIFIED->value);
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                },
            ]);

        return $query->get()->map(function ($user) {
            $user->punctuality = $user->total_attendances > 0
                ? round(($user->on_time_count / $user->total_attendances) * 100, 1)
                : 0;

            return $user;
        });
    }

    /**
     * Export data to Excel format (stub - requires maatwebsite/excel).
     *
     * @return string Path to exported file
     */
    public function exportToExcel(Collection $data, string $filename): string
    {
        // TODO: Implement with maatwebsite/excel if needed
        // For now, return a placeholder
        return "exports/{$filename}.xlsx";
    }

    /**
     * Export data to PDF format (stub - requires barryvdh/laravel-dompdf).
     *
     * @return string Path to exported file
     */
    public function exportToPDF(Collection $data, string $filename): string
    {
        // TODO: Implement with barryvdh/laravel-dompdf if needed
        // For now, return a placeholder
        return "exports/{$filename}.pdf";
    }

    /**
     * Get daily attendance trend.
     *
     * @param  int  $days  Number of days to include
     * @param  int|null  $campusId  Filter by campus
     */
    public function getDailyTrend(int $days = 30, ?int $campusId = null): Collection
    {
        $startDate = now()->subDays($days);

        $query = Attendance::selectRaw('DATE(check_in_time) as date, status, COUNT(*) as count')
            ->where('check_in_time', '>=', $startDate)
            ->groupBy('date', 'status')
            ->orderBy('date');

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        return $query->get()->groupBy('date');
    }

    /**
     * Generate a comprehensive attendance report for a user.
     * Includes: expected days, present days, absences, on-time, late, justified.
     */
    public function generateFullAttendanceReport(
        int $userId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $campusId = null
    ): array {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $user = User::with('schedules')->findOrFail($userId);

        // Obtener días programados (días de la semana que tiene horario activo)
        $scheduleQuery = $user->schedules()->where('is_active', true);
        if ($campusId) {
            $scheduleQuery->where('campus_id', $campusId);
        }
        $scheduledDaysOfWeek = $scheduleQuery->pluck('day_of_week')->unique()->toArray();

        // Calcular días laborables esperados (excluyendo días no laborables)
        $expectedDays = 0;
        $workingDates = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if (in_array($current->dayOfWeek, $scheduledDaysOfWeek)) {
                if (! NonWorkingDay::isNonWorkingDay($current, $campusId)) {
                    $expectedDays++;
                    $workingDates[] = $current->toDateString();
                }
            }
            $current->addDay();
        }

        // Obtener registros de asistencia en el período
        $attendanceQuery = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($campusId) {
            $attendanceQuery->where('campus_id', $campusId);
        }

        $attendances = $attendanceQuery->get();

        // Contar por estado
        $onTime = $attendances->where('status', AttendanceStatus::ON_TIME)->count();
        $late = $attendances->where('status', AttendanceStatus::LATE)->count();
        $absent = $attendances->where('status', AttendanceStatus::ABSENT)->count();
        $justified = $attendances->where('status', AttendanceStatus::JUSTIFIED)->count();
        $present = $onTime + $late + $justified;

        // Calcular porcentajes
        $attendanceRate = $expectedDays > 0 ? round(($present / $expectedDays) * 100, 1) : 0;
        $punctualityRate = $present > 0 ? round(($onTime / $present) * 100, 1) : 0;

        // Obtener días no laborables en el rango
        $nonWorkingDays = NonWorkingDay::getInRange($startDate, $endDate, $campusId);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'expected_days' => $expectedDays,
                'present' => $present,
                'absent' => $absent,
                'on_time' => $onTime,
                'late' => $late,
                'justified' => $justified,
            ],
            'rates' => [
                'attendance_rate' => $attendanceRate,
                'punctuality_rate' => $punctualityRate,
                'absence_rate' => $expectedDays > 0 ? round(($absent / $expectedDays) * 100, 1) : 0,
            ],
            'non_working_days' => $nonWorkingDays->map(fn ($d) => [
                'date' => $d->date->toDateString(),
                'name' => $d->name,
                'type' => $d->type,
            ]),
            'details' => $attendances->map(fn ($a) => [
                'date' => $a->date->toDateString() ?? null,
                'check_in_time' => $a->check_in_time?->format('H:i:s'),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
            ]),
        ];
    }

    /**
     * Generate absence summary report for all users.
     */
    public function generateAbsenceSummaryReport(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $campusId = null
    ): Collection {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return User::where('is_active', true)
            ->whereHas('schedules', fn ($q) => $q->where('is_active', true))
            ->get()
            ->map(function ($user) use ($startDate, $endDate, $campusId) {
                $report = $this->generateFullAttendanceReport(
                    $user->id,
                    $startDate,
                    $endDate,
                    $campusId
                );

                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'expected_days' => $report['summary']['expected_days'],
                    'present' => $report['summary']['present'],
                    'absent' => $report['summary']['absent'],
                    'on_time' => $report['summary']['on_time'],
                    'late' => $report['summary']['late'],
                    'justified' => $report['summary']['justified'],
                    'attendance_rate' => $report['rates']['attendance_rate'],
                    'punctuality_rate' => $report['rates']['punctuality_rate'],
                ];
            })
            ->sortByDesc('absent')
            ->values();
    }
}
