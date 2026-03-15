<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PunctualityWidget;
use App\Filament\Widgets\RecentAttendancesWidget;
use App\Filament\Widgets\SoporteStatsWidget;
use App\Models\Attendance;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class SoporteDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Inicio';

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->isSoporte();
    }

    public function getTitle(): string | Htmlable
    {
        return 'Inicio';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateAbsences')
                ->label('Generar inasistencias')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Generar inasistencias manualmente')
                ->modalDescription('Esto ejecutará el comando de generación de inasistencias para la fecha seleccionada.')
                ->form([
                    DatePicker::make('date')
                        ->label('Fecha')
                        ->default(now()->subDay()->toDateString())
                        ->required(),
                    Toggle::make('force')
                        ->label('Forzar ejecución si el tracking está desactivado')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $date = $data['date'];
                    $force = (bool) ($data['force'] ?? false);
                    $dateObj = \Carbon\Carbon::parse($date);
                    $dayOfWeek = $dateObj->dayOfWeek;

                    // Contar horarios para este día antes de ejecutar
                    $schedulesCount = \App\Models\Schedule::where('day_of_week', $dayOfWeek)
                        ->where('is_active', true)
                        ->whereHas('user', fn($q) => $q->where('is_active', true))
                        ->count();

                    $params = ['--date' => $date];
                    if ($force) {
                        $params['--force'] = true;
                    }

                    $exitCode = Artisan::call('attendance:generate-absences', $params);
                    $output = Artisan::output();

                    $absentCount = Attendance::query()
                        ->whereDate('date', $date)
                        ->where('status', 'absent')
                        ->count();

                    $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                    $dayName = $dayNames[$dayOfWeek];

                    if ($exitCode === 0) {
                        Notification::make()
                            ->title('Inasistencias procesadas')
                            ->body("Fecha: {$date} ({$dayName}). Horarios encontrados: {$schedulesCount}. Total inasistencias: {$absentCount}.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error al procesar')
                            ->body("Ocurrió un error. Revisa los logs del sistema.")
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            SoporteStatsWidget::class,
        ];
    }

    public function getDetailWidgets(): array
    {
        return [
            PunctualityWidget::class,
            RecentAttendancesWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Estadísticas del Sistema')
                    ->description('Usuarios y sedes registrados')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make($this->getColumns())
                            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
                    ]),

                Section::make('Monitoreo de Asistencias')
                    ->description('Puntualidad general y registros recientes')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(3)
                            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getDetailWidgets())),
                    ]),
            ]);
    }
}
