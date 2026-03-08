<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationLabel = 'Parámetros';
    protected static ?string $title = 'Configuración';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        return $user?->isSoporte() ?? false;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return null;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public function mount(): void
    {
        $this->form->fill([
            'institution_name' => Setting::getValue('institution.name'),
            'institution_logo' => Setting::getValue('institution.logo_path'),
            'early_check_in_minutes' => Setting::getValue(
                'attendance.early_check_in_minutes',
                config('attendance.early_check_in_minutes', 30)
            ),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Institución')
                    ->schema([
                        TextInput::make('institution_name')
                            ->label('Nombre de la institución')
                            ->maxLength(255),
                        FileUpload::make('institution_logo')
                            ->label('Logo o escudo')
                            ->disk('public')
                            ->directory('settings')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->visibility('public')
                            ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file)),
                    ])
                    ->columns(2),
                Section::make('Asistencia')
                    ->schema([
                        TextInput::make('early_check_in_minutes')
                            ->label('Minutos de entrada anticipada')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(180)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setValue('institution.name', $state['institution_name'] ?? null);
        Setting::setValue('institution.logo_path', $state['institution_logo'] ?? null);
        Setting::setValue('attendance.early_check_in_minutes', $state['early_check_in_minutes'] ?? null);

        Notification::make()
            ->title('Configuración actualizada')
            ->success()
            ->send();
    }
}
