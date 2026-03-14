<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\AttendanceService;
use App\Services\GeolocationService;
use App\Services\QRGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AttendanceScanner extends Component
{
    public bool $showScanner = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?float $accuracy = null;

    public bool $scanning = false;

    public bool $locationObtained = false;

    public string $locationStatus = 'Esperando ubicación...';

    public string $scanStatus = '';

    public string $errorMessage = '';

    public string $infoMessage = '';

    public bool $registrationSuccess = false;

    public bool $alreadyRegistered = false;

    public ?string $lastStatus = null;

    protected $listeners = [
        'locationReceived',
        'qrScanned',
        'locationError',
    ];

    public function openScanner(): void
    {
        $this->reset(['errorMessage', 'infoMessage', 'scanStatus', 'registrationSuccess', 'alreadyRegistered', 'lastStatus']);
        $this->showScanner = true;
        $this->scanning = true;
        $this->locationStatus = 'Obteniendo ubicación...';

        // Dispatch event to start camera and location
        $this->dispatch('startScanner');
    }

    public function closeScanner(): void
    {
        $this->showScanner = false;
        $this->scanning = false;
        $this->dispatch('stopScanner');
    }

    public function locationReceived(float $lat, float $lon, float $accuracy): void
    {
        $this->latitude = $lat;
        $this->longitude = $lon;
        $this->accuracy = $accuracy;
        $this->locationObtained = true;
        $this->locationStatus = "Ubicación obtenida (±{$accuracy}m)";
    }

    public function locationError(string $message): void
    {
        $this->locationStatus = 'Error: '.$message;
        $this->errorMessage = 'No se pudo obtener la ubicación. Por favor, habilite el GPS.';
    }

    public function qrScanned(string $qrData): void
    {
        // Prevent multiple scans
        if (! $this->scanning) {
            return;
        }

        $this->scanning = false;

        // Check if location is obtained
        if (! $this->locationObtained || ! $this->latitude || ! $this->longitude) {
            $this->errorMessage = 'Esperando ubicación GPS. Por favor, intente de nuevo.';
            $this->scanning = true;

            return;
        }

        // Validate QR token
        $qrService = app(QRGeneratorService::class);
        $campus = $qrService->validateQRToken($qrData);

        if (! $campus) {
            $this->errorMessage = 'Código QR no válido o sede inactiva.';
            $this->scanning = true;

            return;
        }

        // Check if user has schedule assigned for this campus today
        $attendanceService = app(AttendanceService::class);
        /** @var User $user */
        $user = Auth::user();
        $todaySchedule = $attendanceService->getTodaySchedule($user, $campus);

        if (! $todaySchedule) {
            $dayName = now()->locale('es')->isoFormat('dddd');
            if ($user->isDirectivo()) {
                $this->errorMessage = "No tiene horario asignado para el día {$dayName}.";
            } else {
                $this->errorMessage = "No tiene horario asignado en la sede '{$campus->name}' para el día {$dayName}. Verifique que esté escaneando el código QR de la sede correcta.";
            }
            $this->scanning = true;

            return;
        }

        // Check if current time is within schedule time range
        $now = now();
        $checkOutTimeStr = $todaySchedule->check_out_time instanceof \Carbon\Carbon
            ? $todaySchedule->check_out_time->format('H:i:s')
            : $todaySchedule->check_out_time;
        $checkOutTime = \Carbon\Carbon::today()->setTimeFromTimeString($checkOutTimeStr);

        if ($now->gt($checkOutTime)) {
            $this->errorMessage = "El horario de registro para hoy ha finalizado. Su hora de salida programada era a las {$checkOutTime->format('H:i')}.";
            $this->scanning = true;

            return;
        }

        // Check if within radius
        $geoService = app(GeolocationService::class);
        $locationCheck = $geoService->isWithinCampusRadius($this->latitude, $this->longitude, $campus);

        if (! $locationCheck['within_radius']) {
            $distance = $geoService->formatDistance($locationCheck['distance']);
            $this->errorMessage = "Su teléfono móvil indica que usted está a {$distance} de la sede {$campus->name}. Revise su configuración o acérquese a la sede correspondiente.";
            $this->scanning = true;

            return;
        }

        // Check if already registered today
        if ($attendanceService->hasRegisteredToday($user, $campus)) {
            $this->alreadyRegistered = true;
            $this->infoMessage = $user->isDirectivo()
                ? 'Ya ha registrado su asistencia hoy.'
                : 'Ya ha registrado su asistencia hoy en esta sede.';
            $this->closeScanner();

            return;
        }

        // Register attendance
        try {
            $attendance = $attendanceService->registerAttendance(
                $user,
                $campus,
                $this->latitude,
                $this->longitude,
                $locationCheck['distance'],
                request()
            );

            $this->registrationSuccess = true;
            $this->lastStatus = $attendance->status->value;
            $this->scanStatus = "¡Asistencia registrada! Estado: {$attendance->status->label()}";
            $this->closeScanner();

            // Refresh the page after a short delay
            $this->dispatch('attendanceRegistered', status: $attendance->status->value);
        } catch (\Exception $e) {
            Log::error('Error registering attendance: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'campus_id' => $campus->id,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = 'Error al registrar asistencia. Intente de nuevo.';
            $this->scanning = true;
        }
    }

    public function render()
    {
        return view('livewire.attendance-scanner');
    }
}
