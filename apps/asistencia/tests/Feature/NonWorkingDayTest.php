<?php

use App\Models\Campus;
use App\Models\NonWorkingDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('NonWorkingDay Model', function () {

    it('can create a non-working day', function () {
        $nonWorkingDay = NonWorkingDay::create([
            'date' => '2026-01-01',
            'name' => 'Año Nuevo',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
            'is_recurring' => true,
        ]);

        expect($nonWorkingDay)->toBeInstanceOf(NonWorkingDay::class);
        expect($nonWorkingDay->name)->toBe('Año Nuevo');
        expect($nonWorkingDay->type)->toBe('holiday');
        expect($nonWorkingDay->is_recurring)->toBeTrue();
    });

    it('can check if a date is a non-working day (exact date)', function () {
        NonWorkingDay::create([
            'date' => '2026-05-01',
            'name' => 'Día del Trabajo',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
            'is_recurring' => false,
        ]);

        expect(NonWorkingDay::isNonWorkingDay('2026-05-01'))->toBeTrue();
        expect(NonWorkingDay::isNonWorkingDay('2026-05-02'))->toBeFalse();
    });

    it('can check if a date is a recurring non-working day', function () {
        NonWorkingDay::create([
            'date' => '2025-01-01', // Fecha del año pasado
            'name' => 'Año Nuevo',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
            'is_recurring' => true,
        ]);

        // Debe coincidir en cualquier año (1 de enero)
        expect(NonWorkingDay::isNonWorkingDay('2026-01-01'))->toBeTrue();
        expect(NonWorkingDay::isNonWorkingDay('2027-01-01'))->toBeTrue();
        expect(NonWorkingDay::isNonWorkingDay('2026-01-02'))->toBeFalse();
    });

    it('can filter non-working days by campus', function () {
        $campus = Campus::factory()->create(['name' => 'Campus Principal']);
        $campus2 = Campus::factory()->create(['name' => 'Campus Secundario']);

        // Día para campus específico
        NonWorkingDay::create([
            'date' => '2026-03-15',
            'name' => 'Día especial campus principal',
            'type' => NonWorkingDay::TYPE_SPECIAL,
            'campus_id' => $campus->id,
        ]);

        // Día global (todas las sedes)
        NonWorkingDay::create([
            'date' => '2026-03-20',
            'name' => 'Día especial todas las sedes',
            'type' => NonWorkingDay::TYPE_SPECIAL,
            'campus_id' => null,
        ]);

        // El 15 de marzo solo aplica al campus principal
        expect(NonWorkingDay::isNonWorkingDay('2026-03-15', $campus->id))->toBeTrue();
        expect(NonWorkingDay::isNonWorkingDay('2026-03-15', $campus2->id))->toBeFalse();

        // El 20 de marzo aplica a todas las sedes
        expect(NonWorkingDay::isNonWorkingDay('2026-03-20', $campus->id))->toBeTrue();
        expect(NonWorkingDay::isNonWorkingDay('2026-03-20', $campus2->id))->toBeTrue();
    });

    it('returns correct type labels', function () {
        $labels = NonWorkingDay::getTypeLabels();

        expect($labels)->toHaveKey('holiday');
        expect($labels)->toHaveKey('vacation');
        expect($labels)->toHaveKey('special');
        expect($labels['holiday'])->toBe('Festivo');
    });

    it('can get non-working days in a date range', function () {
        NonWorkingDay::create([
            'date' => '2026-06-01',
            'name' => 'Inicio vacaciones',
            'type' => NonWorkingDay::TYPE_VACATION,
        ]);

        NonWorkingDay::create([
            'date' => '2026-06-15',
            'name' => 'Festivo',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
        ]);

        NonWorkingDay::create([
            'date' => '2026-07-01',
            'name' => 'Fuera de rango',
            'type' => NonWorkingDay::TYPE_HOLIDAY,
        ]);

        $startDate = Carbon::parse('2026-06-01');
        $endDate = Carbon::parse('2026-06-30');

        $days = NonWorkingDay::getInRange($startDate, $endDate);

        expect($days)->toHaveCount(2);
    });

});
