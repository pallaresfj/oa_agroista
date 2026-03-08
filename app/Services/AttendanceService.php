<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\NonWorkingDay;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceService
{
    public function __construct(
        private GeolocationService $geolocationService
    ) {
    }

    /**
     * Register attendance for a user.
     *
     * @param User $user
     * @param Campus $campus
     * @param float $latitude
     * @param float $longitude
     * @param float|null $distance
     * @param Request|null $request
     * @return Attendance
     */
    public function registerAttendance(
        User $user,
        Campus $campus,
        float $latitude,
        float $longitude,
        ?float $distance = null,
        ?Request $request = null
    ): Attendance {
        // Get today's schedule for this user at this campus
        $schedule = $this->getTodaySchedule($user, $campus);

        // Calculate distance if not provided
        if ($distance === null) {
            $locationCheck = $this->geolocationService->isWithinCampusRadius(
                $latitude,
                $longitude,
                $campus
            );
            $distance = $locationCheck['distance'];
        }

        // Calculate status based on schedule
        $status = $this->calculateAttendanceStatus($schedule);

        // Build device info from request
        $deviceInfo = null;
        if ($request) {
            $deviceInfo = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];
        }

        // Create attendance record
        return Attendance::create([
            'user_id' => $user->id,
            'campus_id' => $campus->id,
            'schedule_id' => $schedule?->id,
            'date' => now()->toDateString(),
            'check_in_time' => now(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_from_campus' => $distance,
            'status' => $status,
            'device_info' => $deviceInfo,
        ]);
    }

    /**
     * Get today's schedule for a user at a campus.
     *
     * @param User $user
     * @param Campus $campus
     * @return Schedule|null
     */
    public function getTodaySchedule(User $user, Campus $campus): ?Schedule
    {
        $dayOfWeek = now()->dayOfWeek;
        $query = Schedule::where('user_id', $user->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('check_in_time');

        if (!$user->isDirectivo()) {
            $query->where('campus_id', $campus->id);
        }

        $schedules = $query->get();
        if ($schedules->isEmpty()) {
            return null;
        }

        $now = now();
        $earlyCheckInMinutes = (int) Setting::getValue(
            'attendance.early_check_in_minutes',
            config('attendance.early_check_in_minutes', 30)
        );
        foreach ($schedules as $schedule) {
            $checkInTimeStr = $schedule->check_in_time instanceof Carbon
                ? $schedule->check_in_time->format('H:i:s')
                : $schedule->check_in_time;
            $checkOutTimeStr = $schedule->check_out_time instanceof Carbon
                ? $schedule->check_out_time->format('H:i:s')
                : $schedule->check_out_time;

            if (!$checkInTimeStr) {
                continue;
            }

            $checkInTime = Carbon::today()->setTimeFromTimeString($checkInTimeStr);
            $earlyCheckIn = $checkInTime->copy()->subMinutes($earlyCheckInMinutes);
            if ($checkOutTimeStr) {
                $checkOutTime = Carbon::today()->setTimeFromTimeString($checkOutTimeStr);
                if ($now->between($earlyCheckIn, $checkOutTime)) {
                    return $schedule;
                }
            } else {
                if ($now->gte($earlyCheckIn)) {
                    return $schedule;
                }
            }
        }

        return null;
    }

    /**
     * Calculate attendance status based on schedule.
     *
     * @param Schedule|null $schedule
     * @return AttendanceStatus
     */
    public function calculateAttendanceStatus(?Schedule $schedule): AttendanceStatus
    {
        if (!$schedule) {
            // No schedule for today, default to on_time
            return AttendanceStatus::ON_TIME;
        }

        $now = now();
        
        // Get the scheduled check-in time as a string (H:i format)
        $checkInTimeStr = $schedule->check_in_time instanceof Carbon 
            ? $schedule->check_in_time->format('H:i:s')
            : $schedule->check_in_time;
        
        // Create today's check-in time
        $scheduledTime = Carbon::today()->setTimeFromTimeString($checkInTimeStr);
        
        // Add tolerance minutes to get the late threshold
        $toleranceMinutes = $schedule->tolerance_minutes ?? 0;
        $lateThreshold = $scheduledTime->copy()->addMinutes($toleranceMinutes);

        // Log for debugging
        \Illuminate\Support\Facades\Log::info('Attendance status calculation', [
            'current_time' => $now->format('H:i:s'),
            'scheduled_time' => $scheduledTime->format('H:i:s'),
            'tolerance_minutes' => $toleranceMinutes,
            'late_threshold' => $lateThreshold->format('H:i:s'),
            'is_on_time' => $now->lte($lateThreshold),
        ]);

        if ($now->lte($lateThreshold)) {
            return AttendanceStatus::ON_TIME;
        }

        return AttendanceStatus::LATE;
    }

    /**
     * Check if user has already registered attendance today for a campus.
     *
     * @param User $user
     * @param Campus $campus
     * @return bool
     */
    public function hasRegisteredToday(User $user, Campus $campus): bool
    {
        $query = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', today());

        if (!$user->isDirectivo()) {
            $query->where('campus_id', $campus->id);
        }

        return $query->exists();
    }

    /**
     * Get user statistics for a date range.
     *
     * @param User $user
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getUserStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $stats = [
            'total' => $attendances->count(),
            'on_time' => $attendances->where('status', AttendanceStatus::ON_TIME)->count(),
            'late' => $attendances->where('status', AttendanceStatus::LATE)->count(),
            'absent' => $attendances->where('status', AttendanceStatus::ABSENT)->count(),
            'justified' => $attendances->where('status', AttendanceStatus::JUSTIFIED)->count(),
        ];

        // Calculate punctuality percentage
        $stats['punctuality'] = $stats['total'] > 0
            ? round(($stats['on_time'] / $stats['total']) * 100, 1)
            : 0;

        // Calculate attendance rate (present / expected)
        $workDays = $this->getWorkDaysCount($user, $startDate, $endDate);
        $stats['attendance_rate'] = $workDays > 0
            ? round((($stats['on_time'] + $stats['late'] + $stats['justified']) / $workDays) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get expected work days count based on user schedules.
     * Excludes non-working days when absence tracking is enabled.
     *
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $campusId Optional campus filter for non-working days
     * @return int
     */
    private function getWorkDaysCount(User $user, Carbon $startDate, Carbon $endDate, ?int $campusId = null): int
    {
        $scheduledDays = $user->schedules()
            ->where('is_active', true)
            ->pluck('day_of_week')
            ->toArray();

        if (empty($scheduledDays)) {
            return 0;
        }

        $count = 0;
        $current = $startDate->copy();
        $checkNonWorkingDays = config('attendance.absence_tracking_enabled', false);

        while ($current->lte($endDate)) {
            if (in_array($current->dayOfWeek, $scheduledDays)) {
                // Si el tracking de ausencias está habilitado, excluir días no laborables
                if ($checkNonWorkingDays && $this->isNonWorkingDay($current, $campusId)) {
                    $current->addDay();
                    continue;
                }
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Check if a date is a non-working day.
     *
     * @param Carbon|string $date
     * @param int|null $campusId
     * @return bool
     */
    public function isNonWorkingDay(Carbon|string $date, ?int $campusId = null): bool
    {
        return NonWorkingDay::isNonWorkingDay($date, $campusId);
    }

    /**
     * Check if today is a working day for a specific campus.
     *
     * @param int|null $campusId
     * @return bool
     */
    public function isWorkingDay(?int $campusId = null): bool
    {
        return !$this->isNonWorkingDay(now(), $campusId);
    }

    /**
     * Get non-working days in a date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $campusId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNonWorkingDaysInRange(Carbon $startDate, Carbon $endDate, ?int $campusId = null)
    {
        return NonWorkingDay::getInRange($startDate, $endDate, $campusId);
    }

    /**
     * Get today's attendance for a user.
     *
     * @param User $user
     * @return Attendance|null
     */
    public function getTodayAttendance(User $user): ?Attendance
    {
        return Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', today())
            ->first();
    }
}
