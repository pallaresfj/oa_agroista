<?php

namespace App\Support\Institution;

use Agroista\Core\Institution\InstitutionContext;
use Filament\Support\Colors\Color;
use Throwable;

class InstitutionTheme
{
    /**
     * @var array<string, mixed>|null
     */
    private static ?array $institutionCache = null;

    /**
     * @var array<string, string>|null
     */
    private static ?array $paletteCache = null;

    /**
     * @var array{name: string, logo_url: ?string, nit: string, location: string, palette: array<string, string>, primary_color: string, secondary_color: string}|null
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
        $institution = self::institution();
        $storedPalette = $institution['settings']['color_palette'] ?? null;

        if (is_array($storedPalette)) {
            foreach (array_keys($palette) as $key) {
                $color = self::normalizeColor($storedPalette[$key] ?? null);

                if ($color !== null) {
                    $palette[$key] = $color;
                }
            }
        }

        $primary = self::normalizeColor($institution['primary_color'] ?? null);
        $secondary = self::normalizeColor($institution['secondary_color'] ?? null);

        if ($primary !== null) {
            $palette['primary'] = $primary;
        }

        if ($secondary !== null) {
            $palette['success'] = $secondary;
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
     * @return array{name: string, logo_url: ?string, nit: string, location: string, palette: array<string, string>, primary_color: string, secondary_color: string}
     */
    public static function branding(): array
    {
        if (self::$brandingCache !== null) {
            return self::$brandingCache;
        }

        $institution = self::institution();
        $palette = self::palette();
        $name = trim((string) ($institution['name'] ?? ''));
        $logoUrl = trim((string) ($institution['logo_url'] ?? ''));
        $nit = trim((string) ($institution['settings']['nit'] ?? ''));
        $location = trim((string) ($institution['settings']['location'] ?? 'Pivijay, Magdalena - Colombia'));

        self::$brandingCache = [
            'name' => $name !== '' ? $name : (string) config('app.name', 'Institucion'),
            'logo_url' => $logoUrl !== '' ? $logoUrl : null,
            'nit' => $nit,
            'location' => $location !== '' ? $location : 'Pivijay, Magdalena - Colombia',
            'palette' => $palette,
            'primary_color' => $palette['primary'],
            'secondary_color' => $palette['success'],
        ];

        return self::$brandingCache;
    }

    /**
     * @return array<string, mixed>
     */
    private static function institution(): array
    {
        if (self::$institutionCache !== null) {
            return self::$institutionCache;
        }

        try {
            /** @var InstitutionContext $context */
            $context = app(InstitutionContext::class);
            $institution = $context->institution();
        } catch (Throwable) {
            self::$institutionCache = [];

            return self::$institutionCache;
        }

        self::$institutionCache = is_array($institution) ? $institution : [];

        return self::$institutionCache;
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
