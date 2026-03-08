<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\NonWorkingDay;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ReportService - Full Attendance Report', function () {

    it('calculates expected work days correctly', function () {
        $reportService = app(ReportService::class);
        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            Schedule::create([
                'user_id' => $teacher->id,
                'campus_id' => $campus->id,
                'day_of_week' => $day,
                'check_in_time' => '08:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
            ]);
        }

        $startDate = Carbon::parse('2026-02-02');
        $endDate = Carbon::parse('2026-02-06');

        $report = $reportService->generateFullAttendanceReport(
            $teacher->id,
            $startDate,
            $endDate
        );

        expect($report['summary']['expected_days'])->toBe(5);
    });

    it('excludes non-working days from expected days', function () {
        $reportService = app(ReportService::class);
        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            Schedule::create([
                'user_id' => $teacher->id,
                'campus_id' => $campus->id,
                'day_of_week' => $day,
                'check_in_time' => '08:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
            ]);
        }

        $startDate = Carbon::parse('2026-02-02');
        $endDate = Carbon::parse('2026-02-06');

        NonWorkingDay::create([
            'date' => '2026-02-04',
            'name' => 'Festivo',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
        ]);

        $report = $reportService->generateFullAttendanceReport(
            $teacher->id,
            $startDate,
            $endDate
        );

        expect($report['summary']['expected_days'])->toBe(4);
    });

    it('counts attendance statuses correctly', function () {
        $reportService = app(ReportService::class);
        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            Schedule::create([
                'user_id' => $teacher->id,
                'campus_id' => $campus->id,
                'day_of_week' => $day,
                'check_in_time' => '08:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
            ]);
        }

        $startDate = Carbon::parse('2026-02-02');
        $endDate = Carbon::parse('2026-02-06');

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-02',
            'check_in_time' => Carbon::parse('2026-02-02 08:00:00'),
            'status' => AttendanceStatus::ON_TIME,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-03',
            'check_in_time' => Carbon::parse('2026-02-03 08:30:00'),
            'status' => AttendanceStatus::LATE,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-04',
            'check_in_time' => null,
            'status' => AttendanceStatus::ABSENT,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-05',
            'check_in_time' => null,
            'status' => AttendanceStatus::JUSTIFIED,
        ]);

        $report = $reportService->generateFullAttendanceReport(
            $teacher->id,
            $startDate,
            $endDate
        );

        expect($report['summary']['on_time'])->toBe(1);
        expect($report['summary']['late'])->toBe(1);
        expect($report['summary']['absent'])->toBe(1);
        expect($report['summary']['justified'])->toBe(1);
        expect($report['summary']['present'])->toBe(3);
    });

    it('calculates attendance rate correctly', function () {
        $reportService = app(ReportService::class);
        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            Schedule::create([
                'user_id' => $teacher->id,
                'campus_id' => $campus->id,
                'day_of_week' => $day,
                'check_in_time' => '08:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
            ]);
        }

        $startDate = Carbon::parse('2026-02-02');
        $endDate = Carbon::parse('2026-02-06');

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-02',
            'check_in_time' => Carbon::parse('2026-02-02 08:00:00'),
            'status' => AttendanceStatus::ON_TIME,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-03',
            'check_in_time' => Carbon::parse('2026-02-03 08:00:00'),
            'status' => AttendanceStatus::ON_TIME,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-04',
            'check_in_time' => Carbon::parse('2026-02-04 08:30:00'),
            'status' => AttendanceStatus::LATE,
        ]);

        $report = $reportService->generateFullAttendanceReport(
            $teacher->id,
            $startDate,
            $endDate
        );

        expect($report['rates']['attendance_rate'])->toBe(60.0);
    });

    it('calculates punctuality rate correctly', function () {
        $reportService = app(ReportService::class);
        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            Schedule::create([
                'user_id' => $teacher->id,
                'campus_id' => $campus->id,
                'day_of_week' => $day,
                'check_in_time' => '08:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
            ]);
        }

        $startDate = Carbon::parse('2026-02-02');
        $endDate = Carbon::parse('2026-02-06');

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-02',
            'check_in_time' => Carbon::parse('2026-02-02 08:00:00'),
            'status' => AttendanceStatus::ON_TIME,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-03',
            'check_in_time' => Carbon::parse('2026-02-03 08:00:00'),
            'status' => AttendanceStatus::ON_TIME,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-04',
            'check_in_time' => Carbon::parse('2026-02-04 08:30:00'),
            'status' => AttendanceStatus::LATE,
        ]);

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => '2026-02-05',
            'check_in_time' => Carbon::parse('2026-02-05 08:45:00'),
            'status' => AttendanceStatus::LATE,
        ]);

        $report = $reportService->generateFullAttendanceReport(
            $teacher->id,
            $startDate,
            $endDate
        );

        expect($report['rates']['punctuality_rate'])->toBe(50.0);
    });

    it('includes non-working days info in report', function () {
        $reportService = app(ReportService::class);
        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            Schedule::create([
                'user_id' => $teacher->id,
                'campus_id' => $campus->id,
                'day_of_week' => $day,
                'check_in_time' => '08:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
            ]);
        }

        $startDate = Carbon::parse('2026-02-02');
        $endDate = Carbon::parse('2026-02-06');

        NonWorkingDay::create([
            'date' => '2026-02-04',
            'name' => 'Festivo Nacional',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
        ]);

        $report = $reportService->generateFullAttendanceReport(
            $teacher->id,
            $startDate,
            $endDate
        );

        expect($report['non_working_days'])->toHaveCount(1);
        expect($report['non_working_days'][0]['name'])->toBe('Festivo Nacional');
    });

});

describe('ReportService - Absence Summary Report', function () {

    it('generates summary for all users', function () {
        $reportService = app(ReportService::class);
        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) {
            Schedule::create([
                'user_id' => $teacher->id,
                'campus_id' => $campus->id,
                'day_of_week' => $day,
                'check_in_time' => '08:00:00',
                'check_out_time' => '12:00:00',
                'is_active' => true,
            ]);
        }

        $teacher2 = User::factory()->create(['name' => 'Profesor 2', 'is_active' => true]);

        Schedule::create([
            'user_id' => $teacher2->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $startDate = Carbon::parse('2026-02-02');
        $endDate = Carbon::parse('2026-02-06');

        $summary = $reportService->generateAbsenceSummaryReport(
            $startDate,
            $endDate
        );

        expect($summary)->toHaveCount(2);
        expect($summary->pluck('user_name')->toArray())->toContain('Profesor Test', 'Profesor 2');
    });

});
