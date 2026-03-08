<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Early Check-in Window (minutes)
    |--------------------------------------------------------------------------
    |
    | How many minutes before the scheduled check-in time a user is allowed
    | to register attendance and still be considered on time.
    |
    */
    'early_check_in_minutes' => (int) env('ATTENDANCE_EARLY_CHECK_IN_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Absence Tracking Feature Flag
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will automatically generate ABSENT records
    | for users who don't register attendance on their scheduled days.
    | This also enables enhanced reporting features.
    |
    */
    'absence_tracking_enabled' => (bool) env('ABSENCE_TRACKING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Absence Generation Time
    |--------------------------------------------------------------------------
    |
    | The hour (0-23) at which the daily absence generation command should run.
    | This is used by the scheduler to determine when to mark absences.
    |
    */
    'absence_generation_hour' => (int) env('ABSENCE_GENERATION_HOUR', 23),

    /*
    |--------------------------------------------------------------------------
    | Absence Tracking Start Date
    |--------------------------------------------------------------------------
    |
    | When absence tracking is enabled, this date determines from when
    | the system should start generating absence records. This prevents
    | retroactive generation of old absences.
    | Format: Y-m-d (e.g., 2026-02-01)
    |
    */
    'absence_tracking_start_date' => env('ABSENCE_TRACKING_START_DATE', null),
];
