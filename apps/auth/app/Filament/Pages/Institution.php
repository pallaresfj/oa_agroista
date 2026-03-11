<?php

namespace App\Filament\Pages;

use App\Models\InstitutionSetting;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class Institution extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Institución';

    protected static ?string $navigationLabel = 'Institución';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.institution';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getFormDefaults());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tagline')
                    ->label('Eslogan')
                    ->maxLength(255),
                TextInput::make('location')
                    ->label('Ubicación')
                    ->maxLength(255),
                TextInput::make('nit')
                    ->label('NIT')
                    ->required()
                    ->maxLength(100),
                TextInput::make('logo_url')
                    ->label('Logo (URL)')
                    ->url()
                    ->maxLength(2048)
                    ->required(),
                Grid::make(5)
                    ->schema([
                        ColorPicker::make('palette.primary')
                            ->label('Color primario')
                            ->hex()
                            ->required()
                            ->live()
                            ->rule('regex:/^#[0-9A-Fa-f]{6}$/')
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $this->syncColorPalettePreview($set, $get);
                            }),
                        ColorPicker::make('palette.success')
                            ->label('Color success')
                            ->hex()
                            ->required()
                            ->live()
                            ->rule('regex:/^#[0-9A-Fa-f]{6}$/')
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $this->syncColorPalettePreview($set, $get);
                            }),
                        ColorPicker::make('palette.info')
                            ->label('Color info')
                            ->hex()
                            ->required()
                            ->live()
                            ->rule('regex:/^#[0-9A-Fa-f]{6}$/')
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $this->syncColorPalettePreview($set, $get);
                            }),
                        ColorPicker::make('palette.warning')
                            ->label('Color warning')
                            ->hex()
                            ->required()
                            ->live()
                            ->rule('regex:/^#[0-9A-Fa-f]{6}$/')
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $this->syncColorPalettePreview($set, $get);
                            }),
                        ColorPicker::make('palette.danger')
                            ->label('Color danger')
                            ->hex()
                            ->required()
                            ->live()
                            ->rule('regex:/^#[0-9A-Fa-f]{6}$/')
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $this->syncColorPalettePreview($set, $get);
                            }),
                    ]),
                Textarea::make('color_palette_preview')
                    ->label('Paleta de colores (JSON generado)')
                    ->rows(8)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Referencia de solo lectura. Se genera automáticamente desde los selectores de color.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $palette = $this->normalizePalette($state['palette'] ?? null);

        foreach ($palette as $key => $value) {
            if (! $this->isValidHexColor($value)) {
                Notification::make()->title("La clave {$key} debe tener formato hexadecimal (#RRGGBB)")->danger()->send();

                return;
            }
        }

        $this->upsertSetting('name', 'string', (string) ($state['name'] ?? ''), null);
        $this->upsertSetting('tagline', 'string', (string) ($state['tagline'] ?? ''), null);
        $this->upsertSetting('location', 'string', (string) ($state['location'] ?? ''), null);
        $this->upsertSetting('nit', 'string', (string) ($state['nit'] ?? ''), null);
        $this->upsertSetting('logo_url', 'string', (string) ($state['logo_url'] ?? ''), null);
        $this->upsertSetting('color_palette', 'json', null, $palette);

        Notification::make()->title('Institución actualizada')->success()->send();
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormDefaults(): array
    {
        $settings = InstitutionSetting::query()
            ->whereIn('key', ['name', 'tagline', 'location', 'nit', 'logo_url', 'color_palette'])
            ->get()
            ->mapWithKeys(function (InstitutionSetting $setting): array {
                if ($setting->value_json !== null) {
                    return [$setting->key => $setting->value_json];
                }

                return [$setting->key => (string) $setting->value_text];
            });

        $palette = $settings->get('color_palette');

        if (! is_array($palette)) {
            $palette = [];
        }

        $palette = $this->normalizePalette($palette);

        return [
            'name' => (string) $settings->get('name', config('sso.institution_default_name', 'Institucion')),
            'tagline' => (string) $settings->get('tagline', 'Educacion Agropecuaria de Excelencia'),
            'location' => (string) $settings->get('location', 'Pivijay, Magdalena - Colombia'),
            'nit' => (string) $settings->get('nit', ''),
            'logo_url' => (string) $settings->get('logo_url', ''),
            'palette' => $palette,
            'color_palette_preview' => json_encode($palette, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @param  array<string, string>|null  $valueJson
     */
    private function upsertSetting(string $key, string $type, ?string $valueText, ?array $valueJson): void
    {
        InstitutionSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'value_text' => $valueText,
                'value_json' => $valueJson,
                'is_public' => true,
            ],
        );
    }

    private function syncColorPalettePreview(Set $set, Get $get): void
    {
        $palette = $this->normalizePalette($get('palette'));
        $set('color_palette_preview', json_encode($palette, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, string>
     */
    private function defaultPalette(): array
    {
        return [
            'primary' => '#f50404',
            'success' => '#00c853',
            'info' => '#0288d1',
            'warning' => '#ff9800',
            'danger' => '#b71c1c',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function normalizePalette(mixed $palette): array
    {
        $source = is_array($palette) ? $palette : [];
        $normalized = $this->defaultPalette();

        foreach (array_keys($normalized) as $key) {
            $value = trim((string) ($source[$key] ?? ''));

            if ($value === '') {
                continue;
            }

            $normalized[$key] = mb_strtolower($value);
        }

        return $normalized;
    }

    private function isValidHexColor(string $value): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', trim($value)) === 1;
    }
}
