<?php

namespace App\Filament\Resources\NonWorkingDays\Pages;

use App\Filament\Resources\NonWorkingDays\NonWorkingDayResource;
use App\Models\NonWorkingDay;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateVacationRange extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = NonWorkingDayResource::class;

    protected string $view = 'filament.resources.non-working-days.pages.create-vacation-range';

    protected static ?string $title = 'Crear Vacaciones (Rango de fechas)';

    protected static string|BackedEnum|null $navigationIcon = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'type' => NonWorkingDay::TYPE_VACATION,
            'exclude_weekends' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rango de fechas')
                    ->description('Cree múltiples días no laborables de una sola vez')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Vacaciones de mitad de año')
                            ->columnSpanFull(),

                        DatePicker::make('start_date')
                            ->label('Fecha de inicio')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('end_date')
                            ->label('Fecha de fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date'),

                        Select::make('type')
                            ->label('Tipo')
                            ->options(NonWorkingDay::getTypeLabels())
                            ->required()
                            ->default(NonWorkingDay::TYPE_VACATION),

                        Select::make('campus_id')
                            ->label('Sede')
                            ->options(
                                \App\Models\Campus::pluck('name', 'id')->toArray()
                            )
                            ->placeholder('Todas las sedes')
                            ->helperText('Deje vacío para aplicar a todas las sedes')
                            ->searchable(),

                        Checkbox::make('exclude_weekends')
                            ->label('Excluir sábados y domingos')
                            ->helperText('Los fines de semana generalmente no requieren registro si no hay horarios programados')
                            ->default(true),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->placeholder('Notas adicionales')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $excludeWeekends = $data['exclude_weekends'] ?? true;

        $period = CarbonPeriod::create($startDate, $endDate);
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($period as $date) {
            // Excluir fines de semana si está marcada la opción
            if ($excludeWeekends && $date->isWeekend()) {
                $skippedCount++;

                continue;
            }

            // Verificar si ya existe un día no laborable en esta fecha
            $exists = NonWorkingDay::whereDate('date', $date->toDateString())
                ->where(function ($q) use ($data) {
                    $q->whereNull('campus_id');
                    if (! empty($data['campus_id'])) {
                        $q->orWhere('campus_id', $data['campus_id']);
                    }
                })
                ->exists();

            if ($exists) {
                $skippedCount++;

                continue;
            }

            NonWorkingDay::create([
                'date' => $date->toDateString(),
                'name' => $data['name'],
                'type' => $data['type'],
                'campus_id' => $data['campus_id'] ?? null,
                'is_recurring' => false, // Las vacaciones no son recurrentes
                'description' => $data['description'] ?? null,
            ]);

            $createdCount++;
        }

        Notification::make()
            ->title('Días no laborables creados')
            ->body("Se crearon {$createdCount} días. Se omitieron {$skippedCount} días (fines de semana o duplicados).")
            ->success()
            ->send();

        $this->redirect(NonWorkingDayResource::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Crear días no laborables')
                ->submit('create'),

            Action::make('cancel')
                ->label('Cancelar')
                ->url(NonWorkingDayResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
