<?php

namespace App\Support\Institution;

use App\Models\InstitutionSetting;
use Filament\Support\Colors\Color;
use Illuminate\Support\Collection;
use Throwable;

class InstitutionTheme
{
    /**
     * @var Collection<string, mixed>|null
     */
    private static ?Collection $settingsCache = null;

    /**
     * @var array<string, string>|null
     */
    private static ?array $paletteCache = null;

    /**
     * @var array{name: string, logo_url: ?string, nit: string, tagline: string, hero_description: string, location: string, name_icon: string, palette: array<string, string>, primary_color: string, secondary_color: string}|null
     */
    private static ?array $brandingCache = null;

    /**
     * @return array<string, string>
     */
    public static function palette(): array
    {
        if (self::$paletteCache !== null) {
            return self::$paletteCache;
        }

        $palette = self::defaultPalette();
        $settings = self::settings();
        $savedPalette = $settings->get('color_palette');

        if (is_array($savedPalette)) {
            foreach (array_keys($palette) as $key) {
                $color = self::normalizeColor($savedPalette[$key] ?? null);

                if ($color !== null) {
                    $palette[$key] = $color;
                }
            }
        }

        self::$paletteCache = $palette;

        return self::$paletteCache;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function filamentColors(): array
    {
        $palette = self::palette();

        return [
            'primary' => Color::hex($palette['primary']),
            'success' => Color::hex($palette['success']),
            'info' => Color::hex($palette['info']),
            'warning' => Color::hex($palette['warning']),
            'danger' => Color::hex($palette['danger']),
        ];
    }

    /**
     * @return array{name: string, logo_url: ?string, nit: string, tagline: string, hero_description: string, location: string, name_icon: string, palette: array<string, string>, primary_color: string, secondary_color: string}
     */
    public static function branding(): array
    {
        if (self::$brandingCache !== null) {
            return self::$brandingCache;
        }

        $settings = self::settings();
        $palette = self::palette();
        $name = trim((string) $settings->get('name', config('sso.institution_default_name', config('app.name', 'Institucion'))));
        $nit = trim((string) $settings->get('nit', ''));
        $logoUrl = trim((string) $settings->get('logo_url', ''));
        $tagline = trim((string) $settings->get('tagline', 'Educacion Agropecuaria de Excelencia'));
        $heroDescription = trim((string) $settings->get('hero_description', 'Bienvenido al Portal Unico de Acceso. Gestiona tu informacion en un entorno seguro, moderno y eficiente disenado para nuestra comunidad educativa.'));
        $location = trim((string) $settings->get('location', 'Pivijay, Magdalena - Colombia'));
        $nameIcon = trim((string) $settings->get('name_icon', 'agriculture'));

        self::$brandingCache = [
            'name' => $name !== '' ? $name : (string) config('app.name', 'Institucion'),
            'logo_url' => $logoUrl !== '' ? $logoUrl : null,
            'nit' => $nit,
            'tagline' => $tagline !== '' ? $tagline : 'Educacion Agropecuaria de Excelencia',
            'hero_description' => $heroDescription !== '' ? $heroDescription : 'Bienvenido al Portal Unico de Acceso. Gestiona tu informacion en un entorno seguro, moderno y eficiente disenado para nuestra comunidad educativa.',
            'location' => $location !== '' ? $location : 'Pivijay, Magdalena - Colombia',
            'name_icon' => $nameIcon !== '' ? $nameIcon : 'agriculture',
            'palette' => $palette,
            'primary_color' => $palette['primary'],
            'secondary_color' => $palette['success'],
        ];

        return self::$brandingCache;
    }

    /**
     * @return Collection<string, mixed>
     */
    private static function settings(): Collection
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        try {
            self::$settingsCache = InstitutionSetting::query()
                ->whereIn('key', ['name', 'nit', 'logo_url', 'tagline', 'hero_description', 'location', 'name_icon', 'color_palette'])
                ->where('is_public', true)
                ->get()
                ->mapWithKeys(function (InstitutionSetting $setting): array {
                    $value = $setting->value_json;

                    if ($value === null) {
                        $value = $setting->value_text;
                    }

                    return [$setting->key => $value];
                });

            return self::$settingsCache;
        } catch (Throwable) {
            self::$settingsCache = collect();

            return self::$settingsCache;
        }
    }

    /**
     * @return array<string, string>
     */
    private static function defaultPalette(): array
    {
        return [
            'primary' => '#f50404',
            'success' => '#00c853',
            'info' => '#0288d1',
            'warning' => '#ff9800',
            'danger' => '#b71c1c',
        ];
    }

    private static function normalizeColor(mixed $value): ?string
    {
        $color = trim((string) $value);

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
            return null;
        }

        return mb_strtolower($color);
    }
}
