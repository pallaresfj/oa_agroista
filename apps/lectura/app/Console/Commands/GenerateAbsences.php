<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\NonWorkingDay;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAbsences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:generate-absences
                            {--date= : Fecha específica para generar ausencias (formato: Y-m-d). Por defecto: ayer}
                            {--dry-run : Simular sin crear registros}
                            {--force : Ejecutar incluso si el feature flag está desactivado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera registros de ausencia (ABSENT) para docentes que no registraron asistencia en sus horarios programados';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Verificar feature flag
        if (! config('attendance.absence_tracking_enabled') && ! $this->option('force')) {
            $this->warn('El tracking de ausencias está desactivado. Use --force para ejecutar de todas formas.');

            return Command::SUCCESS;
        }

        // Determinar la fecha a procesar
        $dateString = $this->option('date');
        $date = $dateString ? Carbon::parse($dateString) : now()->subDay();

        // Verificar fecha de inicio de tracking
        $startDate = config('attendance.absence_tracking_start_date');
        if ($startDate && $date->lt(Carbon::parse($startDate))) {
            $this->info("La fecha {$date->toDateString()} es anterior a la fecha de inicio de tracking ({$startDate}). Omitiendo.");

            return Command::SUCCESS;
        }

        $this->info("Procesando ausencias para: {$date->toDateString()}");

        $dayOfWeek = $date->dayOfWeek;
        $dryRun = $this->option('dry-run');
        $absencesCreated = 0;
        $skippedNonWorkingDay = 0;
        $skippedAlreadyRegistered = 0;

        // Obtener todos los horarios activos para este día de la semana
        $schedules = Schedule::with(['user', 'campus'])
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        $this->info("Horarios activos encontrados: {$schedules->count()}");

        if ($schedules->isEmpty()) {
            $this->info('No hay horarios activos para este día de la semana.');

            return Command::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($schedules->count());
        $progressBar->start();

        DB::beginTransaction();

        try {
            foreach ($schedules as $schedule) {
                $progressBar->advance();

                // Verificar si es día no laborable para este campus
                if (NonWorkingDay::isNonWorkingDay($date, $schedule->campus_id)) {
                    $skippedNonWorkingDay++;

                    continue;
                }

                // Verificar si ya existe un registro de asistencia para este usuario en esta fecha
                $existingAttendance = Attendance::where('user_id', $schedule->user_id)
                    ->whereDate('date', $date)
                    ->exists();

                if ($existingAttendance) {
                    $skippedAlreadyRegistered++;

                    continue;
                }

                // Crear registro de ausencia
                if (! $dryRun) {
                    Attendance::create([
                        'user_id' => $schedule->user_id,
                        'campus_id' => $schedule->campus_id,
                        'schedule_id' => $schedule->id,
                        'date' => $date->toDateString(),
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'latitude' => null,
                        'longitude' => null,
                        'distance_from_campus' => null,
                        'status' => AttendanceStatus::ABSENT,
                        'notes' => 'Generado automáticamente por el sistema',
                        'device_info' => ['generated_by' => 'attendance:generate-absences'],
                    ]);
                }

                $absencesCreated++;

                Log::info('Ausencia generada', [
                    'user_id' => $schedule->user_id,
                    'user_name' => $schedule->user->name ?? 'N/A',
                    'campus_id' => $schedule->campus_id,
                    'date' => $date->toDateString(),
                    'dry_run' => $dryRun,
                ]);
            }

            if (! $dryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al procesar ausencias: {$e->getMessage()}");
            Log::error('Error en GenerateAbsences', [
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }

        $progressBar->finish();
        $this->newLine(2);

        // Resumen
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Horarios procesados', $schedules->count()],
                ['Ausencias generadas', $absencesCreated],
                ['Omitidos (día no laborable)', $skippedNonWorkingDay],
                ['Omitidos (ya registró)', $skippedAlreadyRegistered],
            ]
        );

        if ($dryRun) {
            $this->warn('Modo simulación (--dry-run): No se crearon registros reales.');
        } else {
            $this->info("✓ Se generaron {$absencesCreated} registros de ausencia.");
        }

        return Command::SUCCESS;
    }
}
