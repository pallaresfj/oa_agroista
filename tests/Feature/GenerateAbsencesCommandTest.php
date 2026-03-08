<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\NonWorkingDay;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

describe('GenerateAbsences Command', function () {

    it('generates absence records for teachers without attendance', function () {
        Config::set('attendance.absence_tracking_enabled', true);
        Config::set('attendance.absence_tracking_start_date', '2026-01-01');

        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        Schedule::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $monday = Carbon::parse('2026-02-02');

        artisan('attendance:generate-absences', ['--date' => $monday->toDateString()])
            ->assertExitCode(0);

        $absence = Attendance::where('user_id', $teacher->id)
            ->whereDate('date', $monday)
            ->first();

        expect($absence)->not->toBeNull();
        expect($absence->status)->toBe(AttendanceStatus::ABSENT);
        expect($absence->notes)->toContain('Generado automáticamente');
    });

    it('does not generate absence if teacher already registered', function () {
        Config::set('attendance.absence_tracking_enabled', true);
        Config::set('attendance.absence_tracking_start_date', '2026-01-01');

        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        Schedule::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $monday = Carbon::parse('2026-02-02');

        Attendance::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'date' => $monday->toDateString(),
            'check_in_time' => $monday->copy()->setTime(8, 5),
            'latitude' => 10.0,
            'longitude' => -75.0,
            'status' => AttendanceStatus::ON_TIME,
        ]);

        artisan('attendance:generate-absences', ['--date' => $monday->toDateString()])
            ->assertExitCode(0);

        $attendances = Attendance::where('user_id', $teacher->id)
            ->whereDate('date', $monday)
            ->get();

        expect($attendances)->toHaveCount(1);
        expect($attendances->first()->status)->toBe(AttendanceStatus::ON_TIME);
    });

    it('skips non-working days', function () {
        Config::set('attendance.absence_tracking_enabled', true);
        Config::set('attendance.absence_tracking_start_date', '2026-01-01');

        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        Schedule::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $monday = Carbon::parse('2026-02-02');

        NonWorkingDay::create([
            'date' => $monday->toDateString(),
            'name' => 'Día Festivo',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
        ]);

        artisan('attendance:generate-absences', ['--date' => $monday->toDateString()])
            ->assertExitCode(0);

        $absence = Attendance::where('user_id', $teacher->id)
            ->whereDate('date', $monday)
            ->first();

        expect($absence)->toBeNull();
    });

    it('respects feature flag when disabled', function () {
        Config::set('attendance.absence_tracking_enabled', false);

        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        Schedule::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $monday = Carbon::parse('2026-02-02');

        artisan('attendance:generate-absences', ['--date' => $monday->toDateString()])
            ->assertExitCode(0);

        $absence = Attendance::where('user_id', $teacher->id)
            ->whereDate('date', $monday)
            ->first();

        expect($absence)->toBeNull();
    });

    it('works with --force flag when feature is disabled', function () {
        Config::set('attendance.absence_tracking_enabled', false);
        Config::set('attendance.absence_tracking_start_date', '2026-01-01');

        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        Schedule::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $monday = Carbon::parse('2026-02-02');

        artisan('attendance:generate-absences', [
            '--date' => $monday->toDateString(),
            '--force' => true,
        ])->assertExitCode(0);

        $absence = Attendance::where('user_id', $teacher->id)
            ->whereDate('date', $monday)
            ->first();

        expect($absence)->not->toBeNull();
    });

    it('dry-run mode does not create records', function () {
        Config::set('attendance.absence_tracking_enabled', true);
        Config::set('attendance.absence_tracking_start_date', '2026-01-01');

        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        Schedule::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $monday = Carbon::parse('2026-02-02');

        artisan('attendance:generate-absences', [
            '--date' => $monday->toDateString(),
            '--dry-run' => true,
        ])->assertExitCode(0);

        $absence = Attendance::where('user_id', $teacher->id)
            ->whereDate('date', $monday)
            ->first();

        expect($absence)->toBeNull();
    });

    it('respects absence tracking start date', function () {
        Config::set('attendance.absence_tracking_enabled', true);
        Config::set('attendance.absence_tracking_start_date', '2026-02-01');

        $campus = Campus::factory()->create(['name' => 'Campus Test']);
        $teacher = User::factory()->create([
            'name' => 'Profesor Test',
            'is_active' => true,
        ]);

        Schedule::create([
            'user_id' => $teacher->id,
            'campus_id' => $campus->id,
            'day_of_week' => 1,
            'check_in_time' => '08:00:00',
            'check_out_time' => '12:00:00',
            'is_active' => true,
        ]);

        $oldMonday = Carbon::parse('2026-01-26');

        artisan('attendance:generate-absences', ['--date' => $oldMonday->toDateString()])
            ->assertExitCode(0);

        $absence = Attendance::where('user_id', $teacher->id)
            ->whereDate('date', $oldMonday)
            ->first();

        expect($absence)->toBeNull();
    });

});
